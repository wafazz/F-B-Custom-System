package com.starcoffee.printbridge

import org.json.JSONObject
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

object ReceiptFormatter {
    private const val WIDTH = 32

    fun format(json: JSONObject): List<ReceiptLine> {
        val order = json.getJSONObject("order")
        val branch = json.getJSONObject("branch")
        val items = order.getJSONArray("items")
        val out = mutableListOf<ReceiptLine>()

        out += ReceiptLine(branch.getString("name"), ReceiptLine.Align.CENTER, big = true)
        out += ReceiptLine("Branch: ${branch.getString("code")}", ReceiptLine.Align.CENTER)
        out += ReceiptLine("")

        val created = order.optString("created_at", "")
        val timestamp = runCatching { iso8601.parse(created)?.let { localFmt.format(it) } }.getOrNull() ?: created
        out += ReceiptLine(padPair("Order #${order.getString("number")}", timestamp))
        val type = order.optString("order_type", "")
        val table = order.optString("dine_in_table", "")
        out += ReceiptLine("Type: $type${if (table.isNotEmpty() && table != "null") " · Table $table" else ""}")
        out += ReceiptLine("-".repeat(WIDTH))

        for (i in 0 until items.length()) {
            val item = items.getJSONObject(i)
            val left = "${item.getInt("quantity")} x ${item.getString("name")}"
            out += ReceiptLine(padPair(left, money(item.optDouble("line_total", 0.0))))
            val mods = item.optJSONArray("modifiers")
            if (mods != null) {
                for (j in 0 until mods.length()) {
                    out += ReceiptLine("   + ${mods.getJSONObject(j).optString("name")}")
                }
            }
            val notes = item.optString("notes", "")
            if (notes.isNotEmpty() && notes != "null") {
                out += ReceiptLine("   * $notes")
            }
        }
        out += ReceiptLine("-".repeat(WIDTH))

        out += ReceiptLine(padPair("Subtotal", money(order.optDouble("subtotal", 0.0))), ReceiptLine.Align.RIGHT)
        out += ReceiptLine(padPair("TOTAL", money(order.optDouble("total", 0.0))), ReceiptLine.Align.RIGHT, big = true)
        out += ReceiptLine("")
        out += ReceiptLine("Thank you!", ReceiptLine.Align.CENTER)
        return out
    }

    private fun money(n: Double) = String.format(Locale.ROOT, "RM %.2f", n)

    private fun padPair(left: String, right: String): String {
        val space = (WIDTH - left.length - right.length).coerceAtLeast(1)
        return left + " ".repeat(space) + right
    }

    private val iso8601 = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX", Locale.US)
    private val localFmt = SimpleDateFormat("dd/MM HH:mm", Locale.US)
}
