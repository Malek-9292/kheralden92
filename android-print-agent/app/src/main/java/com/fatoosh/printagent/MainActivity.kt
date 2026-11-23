package com.fatoosh.printagent

import android.app.Activity
import android.content.Intent
import android.os.Build
import android.os.Bundle
import android.view.ViewGroup
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast

class MainActivity : Activity() {
    private lateinit var serverField: EditText
    private lateinit var codeField: EditText
    private lateinit var tokenField: EditText
    private lateinit var statusText: TextView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val prefs = getSharedPreferences("print-agent", MODE_PRIVATE)
        val root = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(36, 42, 36, 36)
        }

        root.addView(TextView(this).apply {
            text = "فتوش الخالدية · Print Agent"
            textSize = 23f
        }, matchWidth())

        root.addView(TextView(this).apply {
            text = "إعداد الاتصال بالطابعة"
            textSize = 15f
            setPadding(0, 12, 0, 22)
        }, matchWidth())

        serverField = field("رابط الخادم مثل https://example.com").apply {
            setText(prefs.getString("server_url", ""))
            inputType = android.text.InputType.TYPE_CLASS_TEXT or android.text.InputType.TYPE_TEXT_VARIATION_URI
        }
        codeField = field("كود الطابعة مثل KITCHEN-01").apply {
            setText(prefs.getString("printer_code", ""))
        }
        tokenField = field("رمز الجهاز السري (اتركه فارغًا إذا تم حفظه مسبقًا)").apply {
            inputType = android.text.InputType.TYPE_CLASS_TEXT or android.text.InputType.TYPE_TEXT_VARIATION_PASSWORD
        }

        root.addView(label("رابط النظام")); root.addView(serverField, matchWidth())
        root.addView(label("كود الطابعة")); root.addView(codeField, matchWidth())
        root.addView(label("رمز الجهاز")); root.addView(tokenField, matchWidth())

        statusText = TextView(this).apply {
            textSize = 13f
            setPadding(0, 22, 0, 18)
            text = "الخدمة متوقفة. وصّل الطابعة بكابل USB OTG.\n${UsbThermalPrinter(this@MainActivity).describeConnectedDevice()}"
        }
        root.addView(statusText, matchWidth())

        root.addView(Button(this).apply {
            text = "حفظ وتشغيل Print Agent"
            setOnClickListener {
                val server = serverField.text.toString().trim().trimEnd('/')
                val code = codeField.text.toString().trim().uppercase()
                val token = tokenField.text.toString().trim()

                if (!server.startsWith("https://")) {
                    Toast.makeText(this@MainActivity, "استخدم رابط HTTPS للخادم.", Toast.LENGTH_LONG).show()
                    return@setOnClickListener
                }
                if (code.isBlank() || (token.isNotBlank() && token.length < 32)) {
                    Toast.makeText(this@MainActivity, "أدخل كود الطابعة ورمز الجهاز الصحيح.", Toast.LENGTH_LONG).show()
                    return@setOnClickListener
                }

                prefs.edit()
                    .putString("server_url", server)
                    .putString("printer_code", code)
                    .apply()
                if (token.isNotBlank()) SecureTokenStore(this@MainActivity).save(token)
                if (SecureTokenStore(this@MainActivity).read().isNullOrBlank()) {
                    Toast.makeText(this@MainActivity, "رمز الطابعة مطلوب.", Toast.LENGTH_LONG).show()
                    return@setOnClickListener
                }

                val service = Intent(this@MainActivity, PrintAgentService::class.java)
                    .setAction(PrintAgentService.ACTION_START)
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                    startForegroundService(service)
                } else {
                    startService(service)
                }
                statusText.text = "تم طلب تشغيل الخدمة. وافق على صلاحية USB عند ظهور الرسالة.\n${UsbThermalPrinter(this@MainActivity).describeConnectedDevice()}"
            }
        }, matchWidth())

        root.addView(Button(this).apply {
            text = "اختبار الطابعة الآن"
            setOnClickListener {
                val printer = UsbThermalPrinter(this@MainActivity)
                if (!printer.isReady()) {
                    printer.requestPermissionIfNeeded()
                    statusText.text = "وصل طابعة USB ووافق على الصلاحية ثم اضغط اختبار مرة أخرى.\n${printer.describeConnectedDevice()}"
                    Toast.makeText(this@MainActivity, "وصل طابعة USB ووافق على الصلاحية ثم اضغط اختبار مرة أخرى.", Toast.LENGTH_LONG).show()
                } else {
                    Thread {
                        val result = runCatching { printer.print(EscPosRenderer.testPage()) }
                        runOnUiThread {
                            statusText.text = if (result.isSuccess) {
                                "تم إرسال صفحة الاختبار للطابعة.\n${printer.describeConnectedDevice()}"
                            } else {
                                "فشل الاختبار: ${result.exceptionOrNull()?.message}\n${printer.describeConnectedDevice()}"
                            }
                            Toast.makeText(
                                this@MainActivity,
                                if (result.isSuccess) "تم إرسال صفحة الاختبار للطابعة." else "فشل الاختبار: ${result.exceptionOrNull()?.message}",
                                Toast.LENGTH_LONG
                            ).show()
                        }
                    }.start()
                }
            }
        }, matchWidth())

        root.addView(Button(this).apply {
            text = "إيقاف Print Agent"
            setOnClickListener {
                stopService(Intent(this@MainActivity, PrintAgentService::class.java))
                statusText.text = "تم إيقاف الخدمة.\n${UsbThermalPrinter(this@MainActivity).describeConnectedDevice()}"
            }
        }, matchWidth())

        root.addView(TextView(this).apply {
            text = """
                مسار الأجهزة اللوحية الصينية (USB Host):
                1) وصّل Epson TM-T20 بكابل USB-A إلى OTG (USB-C) مباشرة — تجنّب موزّعات USB الرخيصة.
                2) فعّل USB Host / OTG إن وُجد في إعدادات النظام أو Engineer Mode.
                3) عند أول توصيل وافق على «السماح بالوصول إلى جهاز USB» وفعّل «دائمًا لهذا الجهاز» إن ظهر الخيار.
                4) عطّل تحسين البطارية لهذا التطبيق (Battery → Unrestricted / لا تحسينات). على Xiaomi/Oppo/Vivo/Huawei أضف التطبيق إلى القائمة البيضاء للتشغيل الذاتي.
                5) أبقِ الشاشة مضاءة أثناء الاختبار الأول؛ بعض الرومات تقتل خدمات المقدمة عند السكون العميق.
                6) إن لم تُكتشف الطابعة: افصلها، انتظر 5 ثوانٍ، أعد التوصيل، ثم افتح التطبيق واضغط اختبار الطابعة.
            """.trimIndent()
            textSize = 12f
            setPadding(0, 22, 0, 0)
        }, matchWidth())

        setContentView(ScrollView(this).apply { addView(root) })
    }

    private fun field(hintText: String) = EditText(this).apply {
        hint = hintText
        textSize = 14f
        setSingleLine(true)
        setPadding(18, 14, 18, 14)
    }

    private fun label(value: String) = TextView(this).apply {
        text = value
        textSize = 13f
        setPadding(0, 14, 0, 6)
    }

    private fun matchWidth() = LinearLayout.LayoutParams(
        ViewGroup.LayoutParams.MATCH_PARENT,
        ViewGroup.LayoutParams.WRAP_CONTENT
    )
}
