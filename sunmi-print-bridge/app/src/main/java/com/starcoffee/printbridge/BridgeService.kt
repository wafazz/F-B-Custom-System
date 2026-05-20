package com.starcoffee.printbridge

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.Service
import android.content.Context
import android.content.Intent
import android.os.Build
import android.os.IBinder
import android.util.Log
import org.json.JSONObject
import java.io.BufferedReader
import java.io.InputStreamReader
import java.io.OutputStreamWriter
import java.net.InetAddress
import java.net.ServerSocket
import kotlin.concurrent.thread

private const val TAG = "BridgeService"
private const val CHANNEL_ID = "sunmi_print_bridge"
private const val NOTIFICATION_ID = 1
private const val PORT = 8765

class BridgeService : Service() {
    private lateinit var printer: SunmiPrinter
    private var server: ServerSocket? = null

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onCreate() {
        super.onCreate()
        ensureChannel()
        startForeground(NOTIFICATION_ID, buildNotification("Listening on 127.0.0.1:$PORT"))
        printer = SunmiPrinter(this)
        printer.bind()
        thread(name = "bridge-http", isDaemon = true) { runServer() }
    }

    override fun onDestroy() {
        super.onDestroy()
        runCatching { server?.close() }
        printer.unbind()
    }

    private fun runServer() {
        // Bind to loopback only — the PWA in Chrome on the same device will
        // hit http://127.0.0.1:8765. We never expose this on the LAN.
        val sock = ServerSocket(PORT, 50, InetAddress.getByName("127.0.0.1"))
        server = sock
        Log.i(TAG, "HTTP server up on 127.0.0.1:$PORT")
        while (!sock.isClosed) {
            val client = runCatching { sock.accept() }.getOrNull() ?: break
            thread(name = "bridge-conn", isDaemon = true) {
                client.use { handle(it.getInputStream(), it.getOutputStream()) }
            }
        }
    }

    private fun handle(input: java.io.InputStream, output: java.io.OutputStream) {
        val reader = BufferedReader(InputStreamReader(input))
        val requestLine = reader.readLine() ?: return
        val (method, path) = parseRequestLine(requestLine)
        var contentLength = 0
        while (true) {
            val header = reader.readLine() ?: break
            if (header.isEmpty()) break
            if (header.startsWith("Content-Length:", ignoreCase = true)) {
                contentLength = header.substringAfter(":").trim().toIntOrNull() ?: 0
            }
        }
        val body = if (contentLength > 0) CharArray(contentLength).also { reader.read(it) }.concatToString() else ""

        val writer = OutputStreamWriter(output)
        try {
            when {
                method == "GET" && path == "/ping" -> handlePing(writer)
                method == "POST" && path == "/print" -> handlePrint(body, writer)
                method == "OPTIONS" -> respondCors(writer)
                else -> respond(writer, 404, "application/json", """{"error":"not found"}""")
            }
        } catch (e: Exception) {
            Log.e(TAG, "request failed", e)
            respond(writer, 500, "application/json", """{"error":${JSONObject.quote(e.message ?: "")}}""")
        }
        writer.flush()
    }

    private fun handlePing(writer: OutputStreamWriter) {
        val payload = JSONObject().apply {
            put("ok", true)
            put("device", "sunmi")
            put("printer_bound", printer.isReady)
            put("bound_package", printer.boundPackage ?: JSONObject.NULL)
            put("last_bind_error", printer.lastBindError ?: JSONObject.NULL)
            put("last_print_error", printer.lastPrintError ?: JSONObject.NULL)
        }
        respond(writer, 200, "application/json", payload.toString())
    }

    private fun handlePrint(body: String, writer: OutputStreamWriter) {
        if (!printer.isReady) {
            val err = JSONObject().apply {
                put("error", "printer not bound")
                put("bound_package", printer.boundPackage ?: JSONObject.NULL)
                put("last_bind_error", printer.lastBindError ?: JSONObject.NULL)
            }
            respond(writer, 503, "application/json", err.toString())
            return
        }
        try {
            val json = JSONObject(body)
            val lines = ReceiptFormatter.format(json)
            printer.printReceipt(lines)
            respond(writer, 200, "application/json", """{"ok":true,"bound_package":${JSONObject.quote(printer.boundPackage ?: "")}}""")
        } catch (e: Exception) {
            Log.e(TAG, "print path failed", e)
            val err = JSONObject().apply {
                put("error", e::class.java.simpleName)
                put("message", e.message ?: "")
                put("last_print_error", printer.lastPrintError ?: JSONObject.NULL)
            }
            respond(writer, 500, "application/json", err.toString())
        }
    }

    private fun parseRequestLine(line: String): Pair<String, String> {
        val parts = line.split(' ')
        return if (parts.size >= 2) parts[0] to parts[1] else "" to ""
    }

    private fun respond(writer: OutputStreamWriter, status: Int, type: String, body: String) {
        val reason = when (status) { 200 -> "OK"; 404 -> "Not Found"; 503 -> "Service Unavailable"; else -> "Error" }
        val bytes = body.toByteArray(Charsets.UTF_8)
        writer.write("HTTP/1.1 $status $reason\r\n")
        writer.write("Access-Control-Allow-Origin: *\r\n")
        writer.write("Access-Control-Allow-Methods: GET, POST, OPTIONS\r\n")
        writer.write("Access-Control-Allow-Headers: Content-Type\r\n")
        writer.write("Content-Type: $type; charset=utf-8\r\n")
        writer.write("Content-Length: ${bytes.size}\r\n")
        writer.write("Connection: close\r\n\r\n")
        writer.write(body)
    }

    private fun respondCors(writer: OutputStreamWriter) {
        writer.write("HTTP/1.1 204 No Content\r\n")
        writer.write("Access-Control-Allow-Origin: *\r\n")
        writer.write("Access-Control-Allow-Methods: GET, POST, OPTIONS\r\n")
        writer.write("Access-Control-Allow-Headers: Content-Type\r\n")
        writer.write("Content-Length: 0\r\n")
        writer.write("Connection: close\r\n\r\n")
    }

    private fun ensureChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val manager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
            val channel = NotificationChannel(CHANNEL_ID, "Print Bridge", NotificationManager.IMPORTANCE_LOW)
            manager.createNotificationChannel(channel)
        }
    }

    private fun buildNotification(text: String): Notification {
        val builder = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O)
            Notification.Builder(this, CHANNEL_ID) else @Suppress("DEPRECATION") Notification.Builder(this)
        return builder
            .setContentTitle("Star Coffee Print Bridge")
            .setContentText(text)
            .setSmallIcon(android.R.drawable.ic_menu_share)
            .setOngoing(true)
            .build()
    }
}
