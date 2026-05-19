package com.starcoffee.printbridge

import android.app.Activity
import android.content.Intent
import android.os.Build
import android.os.Bundle
import android.widget.TextView

class MainActivity : Activity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        val intent = Intent(this, BridgeService::class.java)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            startForegroundService(intent)
        } else {
            startService(intent)
        }

        findViewById<TextView>(R.id.status).text =
            "Print bridge is running on this device.\n\n" +
            "Endpoint: http://127.0.0.1:8765\n\n" +
            "Leave this app installed. Closing the screen is fine — the service stays up."
    }
}
