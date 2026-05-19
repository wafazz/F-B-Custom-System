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

class SunmiPrinter(private val context: Context) {
    @Volatile private var service: IWoyouService? = null

    private val noopCallback = object : ICallback.Stub() {
        override fun onRunResult(isSuccess: Boolean) {}
        override fun onReturnString(result: String?) {}
        override fun onRaiseException(code: Int, msg: String?) {
            Log.w(TAG, "exception code=$code msg=$msg")
        }
        override fun onPrintResult(code: Int, msg: String?) {}
    }

    private val connection = object : ServiceConnection {
        override fun onServiceConnected(name: ComponentName?, binder: IBinder?) {
            service = IWoyouService.Stub.asInterface(binder)
            Log.i(TAG, "SUNMI printer service bound")
        }
        override fun onServiceDisconnected(name: ComponentName?) {
            service = null
            Log.w(TAG, "SUNMI printer service disconnected")
        }
    }

    fun bind() {
        val intent = Intent().apply {
            setPackage("woyou.aidlservice.jiuiv5")
            action = "woyou.aidlservice.jiuiv5.IWoyouService"
        }
        val ok = context.bindService(intent, connection, Context.BIND_AUTO_CREATE)
        if (!ok) Log.e(TAG, "bindService failed — is this a SUNMI device with built-in printer?")
    }

    fun unbind() {
        runCatching { context.unbindService(connection) }
        service = null
    }

    val isReady: Boolean get() = service != null

    fun printReceipt(lines: List<ReceiptLine>) {
        val svc = service ?: error("Printer service not bound yet")
        svc.printerInit(noopCallback)
        for (line in lines) {
            svc.setAlignment(line.align.toAidl(), noopCallback)
            svc.setFontSize(if (line.big) 32f else 24f, noopCallback)
            svc.printText(line.text + "\n", noopCallback)
        }
        svc.lineWrap(4, noopCallback)
        svc.cutPaper(noopCallback)
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
