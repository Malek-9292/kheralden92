package com.fatoosh.printagent

import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.graphics.Typeface
import android.text.Layout
import android.text.StaticLayout
import android.text.TextDirectionHeuristics
import android.text.TextPaint
import org.json.JSONObject
import java.io.ByteArrayOutputStream
import kotlin.math.ceil

object EscPosRenderer {
    private const val PAPER_WIDTH_DOTS = 576

    fun testPage(): ByteArray {
        return render(
            "فتوش الخالدية\n" +
                "اختبار طابعة المطبخ\n" +
                "Epson TM-T20 · USB\n" +
                "--------------------------------\n" +
                "العربية تعمل بشكل صحيح؟\n" +
                "طلب تجريبي 12345\n" +
                "--------------------------------\n\n"
        )
    }

    fun kitchenTicket(payload: JSONObject): ByteArray {
        payload.optJSONArray("lines")?.let { prepared ->
            if (prepared.length() > 0) {
                val lines = mutableListOf<String>()
                for (index in 0 until prepared.length()) {
                    lines += prepared.optString(index)
                }
                lines += ""
                lines += ""
                return render(lines.joinToString("\n"))
            }
        }

        val lines = mutableListOf<String>()
        lines += payload.optString("branch", "فتوش الخالدية")
        lines += "تذكرة المطبخ"
        lines += "رقم الطلب: ${payload.optString("order_number")}"
        lines += payload.optString("created_at")
        lines += "--------------------------------"
        lines += "العميل: ${payload.optString("customer_name")}"
        lines += "الهاتف: ${payload.optString("customer_phone")}"
        lines += if (payload.optString("fulfillment_type") == "delivery") "النوع: توصيل" else "النوع: استلام من المطعم"

        if (payload.optString("fulfillment_type") == "delivery") {
            lines += "المنطقة: ${payload.optString("delivery_area")}"
            lines += "القطعة: ${payload.optString("block")}"
            lines += "الشارع: ${payload.optString("street")}"
            if (payload.optString("avenue").isNotBlank()) lines += "الجادة: ${payload.optString("avenue")}"
            lines += "المنزل: ${payload.optString("house_number")}"
            if (payload.optString("floor").isNotBlank()) lines += "الدور: ${payload.optString("floor")}"
            if (payload.optString("apartment").isNotBlank()) lines += "الشقة: ${payload.optString("apartment")}"
        }

        lines += "--------------------------------"
        val items = payload.optJSONArray("items")
        if (items != null) {
            for (index in 0 until items.length()) {
                val item = items.optJSONObject(index) ?: continue
                lines += item.optString("category")
                lines += "${item.optString("name")}  × ${item.optInt("quantity")}"
            }
        }
        lines += "--------------------------------"
        if (payload.optString("notes").isNotBlank()) {
            lines += "ملاحظات العميل:"
            lines += payload.optString("notes")
            lines += "--------------------------------"
        }
        lines += "المجموع: ${payload.optString("subtotal")} د.ك"
        if (payload.optString("fulfillment_type") == "delivery") {
            lines += "التوصيل: ${payload.optString("delivery_fee")} د.ك"
        }
        lines += "الإجمالي: ${payload.optString("total")} د.ك"
        lines += ""
        lines += ""
        return render(lines.joinToString("\n"))
    }

    fun customerReceipt(payload: JSONObject): ByteArray {
        payload.optJSONArray("lines")?.let { prepared ->
            if (prepared.length() > 0) {
                val lines = mutableListOf<String>()
                for (index in 0 until prepared.length()) {
                    lines += prepared.optString(index)
                }
                lines += "شكرًا لزيارتكم فتوش الخالدية"
                lines += ""
                lines += ""
                return render(lines.joinToString("\n"))
            }
        }

        val lines = mutableListOf<String>()
        lines += payload.optString("branch", "فتوش الخالدية")
        lines += "فاتورة العميل"
        lines += "رقم الطلب: ${payload.optString("order_number")}"
        lines += payload.optString("created_at")
        lines += "--------------------------------"
        lines += "العميل: ${payload.optString("customer_name")}"
        lines += "الهاتف: ${payload.optString("customer_phone")}"
        lines += if (payload.optString("fulfillment_type") == "delivery") "توصيل" else "استلام من المطعم"
        lines += "--------------------------------"
        val items = payload.optJSONArray("items")
        if (items != null) {
            for (index in 0 until items.length()) {
                val item = items.optJSONObject(index) ?: continue
                lines += item.optString("name")
                lines += "${item.optInt("quantity")} × ${item.optString("unit_price")} = ${item.optString("line_total")} د.ك"
            }
        }
        lines += "--------------------------------"
        lines += "المجموع: ${payload.optString("subtotal")} د.ك"
        if (payload.optString("fulfillment_type") == "delivery") {
            lines += "التوصيل: ${payload.optString("delivery_fee")} د.ك"
        }
        lines += "الإجمالي: ${payload.optString("total")} د.ك"
        lines += "شكرًا لزيارتكم فتوش الخالدية"
        lines += ""
        lines += ""
        return render(lines.joinToString("\n"))
    }

    private fun render(text: String): ByteArray {
        val paint = TextPaint(Paint.ANTI_ALIAS_FLAG).apply {
            color = Color.BLACK
            textSize = 27f
            typeface = Typeface.create("sans-serif", Typeface.NORMAL)
        }
        val contentWidth = PAPER_WIDTH_DOTS - 36
        val layout = StaticLayout.Builder.obtain(text, 0, text.length, paint, contentWidth)
            .setAlignment(Layout.Alignment.ALIGN_NORMAL)
            .setTextDirection(TextDirectionHeuristics.RTL)
            .setIncludePad(true)
            .setLineSpacing(8f, 1.0f)
            .build()

        val bitmapHeight = layout.height + 40
        val bitmap = Bitmap.createBitmap(PAPER_WIDTH_DOTS, bitmapHeight, Bitmap.Config.ARGB_8888)
        val canvas = Canvas(bitmap)
        canvas.drawColor(Color.WHITE)
        canvas.save()
        canvas.translate(18f, 18f)
        layout.draw(canvas)
        canvas.restore()

        val widthBytes = ceil(PAPER_WIDTH_DOTS / 8.0).toInt()
        val height = bitmap.height
        val pixels = IntArray(PAPER_WIDTH_DOTS * height)
        bitmap.getPixels(pixels, 0, PAPER_WIDTH_DOTS, 0, 0, PAPER_WIDTH_DOTS, height)

        val output = ByteArrayOutputStream()
        output.write(byteArrayOf(0x1B, 0x40))
        output.write(byteArrayOf(0x1B, 0x61, 0x01))
        output.write(byteArrayOf(0x1D, 0x76, 0x30, 0x00))
        output.write(widthBytes and 0xFF)
        output.write((widthBytes shr 8) and 0xFF)
        output.write(height and 0xFF)
        output.write((height shr 8) and 0xFF)

        for (y in 0 until height) {
            for (xByte in 0 until widthBytes) {
                var value = 0
                for (bit in 0 until 8) {
                    val x = xByte * 8 + bit
                    if (x < PAPER_WIDTH_DOTS) {
                        val color = pixels[y * PAPER_WIDTH_DOTS + x]
                        val darkness = (Color.red(color) + Color.green(color) + Color.blue(color)) / 3
                        if (darkness < 160) value = value or (1 shl (7 - bit))
                    }
                }
                output.write(value)
            }
        }

        output.write(byteArrayOf(0x1B, 0x64, 0x04))
        output.write(byteArrayOf(0x1D, 0x56, 0x41, 0x03))
        bitmap.recycle()
        return output.toByteArray()
    }
}
