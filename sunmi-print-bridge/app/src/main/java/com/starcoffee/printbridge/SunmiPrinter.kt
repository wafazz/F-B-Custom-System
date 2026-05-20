package com.starcoffee.printbridge

import android.content.ComponentName
import android.content.Context
import android.content.Intent
import android.content.ServiceConnection
import android.os.IBinder
import android.util.Log
import woyou.aidlservice.jiuiv5.ICallback
import woyou.aidlservice.jiuiv5.IWoyouService

private const val TAG = "SunmiPrinter"

/**
 * Different SUNMI device families expose the inner printer through
 * different AIDL service hosts. Bind to whichever responds first.
 *
 * - `woyou.aidlservice.jiuiv5`: classic V1/V2/L2/D2 inner printer service
 * - `com.sunmi.extprinterservice`: external / cloud-printer hosts on
 *   some firmware variants that still expose the IWoyouService interface
 */
private val BIND_TARGETS = listOf(
    "woyou.aidlservice.jiuiv5" to "woyou.aidlservice.jiuiv5.IWoyouService",
    "com.sunmi.extprinterservice" to "woyou.aidlservice.jiuiv5.IWoyouService",
)

class SunmiPrinter(private val context: Context) {
    @Volatile private var service: IWoyouService? = null
    @Volatile var lastBindError: String? = null
        private set
    @Volatile var boundPackage: String? = null
        private set
    @Volatile var lastPrintError: String? = null
        private set

    private val noopCallback = object : ICallback.Stub() {
        override fun onRunResult(isSuccess: Boolean) {}
        override fun onReturnString(result: String?) {}
        override fun onRaiseException(code: Int, msg: String?) {
            Log.w(TAG, "exception code=$code msg=$msg")
            lastPrintError = "AIDL exception code=$code msg=$msg"
        }
        override fun onPrintResult(code: Int, msg: String?) {}
    }

    private val connections = mutableListOf<ServiceConnection>()

    fun bind() {
        var attempted = 0
        for ((pkg, action) in BIND_TARGETS) {
            attempted++
            val conn = object : ServiceConnection {
                override fun onServiceConnected(name: ComponentName?, binder: IBinder?) {
                    if (service == null) {
                        service = IWoyouService.Stub.asInterface(binder)
                        boundPackage = pkg
                        Log.i(TAG, "SUNMI printer service bound via $pkg")
                    }
                }
                override fun onServiceDisconnected(name: ComponentName?) {
                    if (boundPackage == pkg) {
                        service = null
                        boundPackage = null
                        Log.w(TAG, "SUNMI printer service disconnected ($pkg)")
                    }
                }
            }
            connections += conn
            val intent = Intent().apply {
                setPackage(pkg)
                this.action = action
            }
            val ok = try {
                context.bindService(intent, conn, Context.BIND_AUTO_CREATE)
            } catch (e: SecurityException) {
                Log.e(TAG, "bindService SecurityException for $pkg", e)
                lastBindError = "SecurityException binding to $pkg: ${e.message}"
                false
            }
            if (!ok) {
                Log.w(TAG, "bindService returned false for $pkg")
                if (lastBindError == null) lastBindError = "bindService returned false for $pkg"
            }
        }
        if (attempted == 0) lastBindError = "no bind targets configured"
    }

    fun unbind() {
        for (conn in connections) runCatching { context.unbindService(conn) }
        connections.clear()
        service = null
        boundPackage = null
    }

    val isReady: Boolean get() = service != null

    fun printReceipt(lines: List<ReceiptLine>) {
        val svc = service ?: run {
            lastPrintError = "Printer service not bound (lastBindError=$lastBindError)"
            error(lastPrintError!!)
        }
        try {
            svc.printerInit(noopCallback)
            for (line in lines) {
                svc.setAlignment(line.align.toAidl(), noopCallback)
                svc.setFontSize(if (line.big) 32f else 24f, noopCallback)
                svc.printText(line.text + "\n", noopCallback)
            }
            svc.lineWrap(4, noopCallback)
            svc.cutPaper(noopCallback)
            lastPrintError = null
        } catch (e: Exception) {
            lastPrintError = "${e::class.java.simpleName}: ${e.message}"
            Log.e(TAG, "print failed", e)
            throw e
        }
    }
}

data class ReceiptLine(val text: String, val align: Align = Align.LEFT, val big: Boolean = false) {
    enum class Align { LEFT, CENTER, RIGHT }
}

private fun ReceiptLine.Align.toAidl(): Int = when (this) {
    ReceiptLine.Align.LEFT -> 0
    ReceiptLine.Align.CENTER -> 1
    ReceiptLine.Align.RIGHT -> 2
}
