package com.fatoosh.printagent

import android.app.Activity
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.hardware.usb.UsbManager
import android.os.Build
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.ViewGroup
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import android.widget.Toast
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone
import kotlinx.coroutines.runBlocking

class MainActivity : Activity() {
    private lateinit var serverField: EditText
    private lateinit var codeField: EditText
    private lateinit var tokenField: EditText
    private lateinit var statusText: TextView
    private lateinit var statusStore: AgentStatusStore
    private val uiHandler = Handler(Looper.getMainLooper())
    private val refreshRunnable = object : Runnable {
        override fun run() {
            refreshStatusPanel()
            uiHandler.postDelayed(this, 1500)
        }
    }

    private val usbPermissionReceiver = object : BroadcastReceiver() {
        override fun onReceive(context: Context?, intent: Intent?) {
            if (intent?.action != UsbThermalPrinter.ACTION_USB_PERMISSION) return
            val granted = intent.getBooleanExtra(UsbManager.EXTRA_PERMISSION_GRANTED, false)
            runOnUiThread {
                refreshStatusPanel(
                    extra = if (granted) "تم منح إذن USB." else "رُفض إذن USB — أعد المحاولة ووافق على النافذة."
                )
            }
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        statusStore = AgentStatusStore(this)

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
            text = "..."
        }
        root.addView(statusText, matchWidth())

        root.addView(Button(this).apply {
            text = "اختبار الاتصال بالخادم"
            setOnClickListener { runConnectionTest() }
        }, matchWidth())

        root.addView(Button(this).apply {
            text = "حفظ وتشغيل Print Agent"
            setOnClickListener { saveAndStart() }
        }, matchWidth())

        root.addView(Button(this).apply {
            text = "اختبار الطابعة الآن"
            setOnClickListener { runPrinterTest() }
        }, matchWidth())

        root.addView(Button(this).apply {
            text = "إيقاف Print Agent"
            setOnClickListener {
                stopService(Intent(this@MainActivity, PrintAgentService::class.java).setAction(PrintAgentService.ACTION_STOP))
                statusStore.setStopped("تم إيقاف الخدمة")
                refreshStatusPanel(extra = "تم إيقاف الخدمة.")
            }
        }, matchWidth())

        root.addView(TextView(this).apply {
            text = """
                مسار سريع عند «مشتغلتش»:
                1) اضغط «اختبار الاتصال بالخادم» — يجب أن يظهر «متصل» ومهام معلّقة.
                2) إن فشل: صحّح الرابط (HTTPS بدون / في النهاية) والكود والرمز.
                3) وصّل Epson USB-OTG ووافق على إذن USB ثم «اختبار الطابعة».
                4) احفظ وشغّل الخدمة؛ في /admin/printers يجب أن تتغيّر «آخر اتصال» خلال ثوانٍ.
            """.trimIndent()
            textSize = 12f
            setPadding(0, 22, 0, 0)
        }, matchWidth())

        setContentView(ScrollView(this).apply { addView(root) })
        refreshStatusPanel()
    }

    override fun onStart() {
        super.onStart()
        val filter = IntentFilter(UsbThermalPrinter.ACTION_USB_PERMISSION)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            registerReceiver(usbPermissionReceiver, filter, RECEIVER_NOT_EXPORTED)
        } else {
            registerReceiver(usbPermissionReceiver, filter)
        }
        uiHandler.post(refreshRunnable)
    }

    override fun onStop() {
        uiHandler.removeCallbacks(refreshRunnable)
        runCatching { unregisterReceiver(usbPermissionReceiver) }
        super.onStop()
    }

    private fun saveAndStart() {
        val prefs = getSharedPreferences("print-agent", MODE_PRIVATE)
        val normalized = ServerUrl.normalize(serverField.text.toString())
        if (normalized.url == null) {
            Toast.makeText(this, normalized.errorAr, Toast.LENGTH_LONG).show()
            refreshStatusPanel(extra = normalized.errorAr)
            return
        }
        val code = codeField.text.toString().trim().uppercase()
        val token = tokenField.text.toString().trim()

        if (code.isBlank() || (token.isNotBlank() && token.length < 32)) {
            Toast.makeText(this, "أدخل كود الطابعة ورمز الجهاز الصحيح (64 حرفًا عادة).", Toast.LENGTH_LONG).show()
            return
        }

        prefs.edit()
            .putString("server_url", normalized.url)
            .putString("printer_code", code)
            .apply()
        serverField.setText(normalized.url)
        codeField.setText(code)

        if (token.isNotBlank()) SecureTokenStore(this).save(token)
        if (SecureTokenStore(this).read().isNullOrBlank()) {
            Toast.makeText(this, "رمز الطابعة مطلوب.", Toast.LENGTH_LONG).show()
            return
        }

        val service = Intent(this, PrintAgentService::class.java).setAction(PrintAgentService.ACTION_START)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            startForegroundService(service)
        } else {
            startService(service)
        }
        refreshStatusPanel(extra = "تم طلب تشغيل الخدمة. وافق على صلاحية USB عند ظهور الرسالة.")
    }

    private fun runConnectionTest() {
        val normalized = ServerUrl.normalize(serverField.text.toString())
        if (normalized.url == null) {
            statusStore.setFailed(normalized.errorAr ?: "رابط غير صالح")
            refreshStatusPanel(extra = normalized.errorAr)
            Toast.makeText(this, normalized.errorAr, Toast.LENGTH_LONG).show()
            return
        }
        val code = codeField.text.toString().trim().uppercase()
        var token = tokenField.text.toString().trim()
        if (token.isBlank()) token = SecureTokenStore(this).read().orEmpty().trim()
        if (code.isBlank() || token.length < 32) {
            Toast.makeText(this, "أدخل كود الطابعة ورمز الجهاز قبل اختبار الاتصال.", Toast.LENGTH_LONG).show()
            return
        }

        statusText.text = "جارٍ اختبار الاتصال…"
        Thread {
            val result = runCatching {
                kotlinx.coroutines.runBlocking {
                    PrintApiClient().heartbeat(normalized.url!!, code, token)
                }
            }
            runOnUiThread {
                result.fold(
                    onSuccess = { json ->
                        val pending = json.optInt("pending_jobs", 0)
                        val name = json.optJSONObject("printer")?.optString("name").orEmpty()
                        val detail = "متصل بنجاح · $name · مهام معلّقة: $pending"
                        getSharedPreferences("print-agent", MODE_PRIVATE).edit()
                            .putString("server_url", normalized.url)
                            .putString("printer_code", code)
                            .apply()
                        if (tokenField.text.toString().trim().isNotBlank()) {
                            SecureTokenStore(this).save(token)
                        }
                        statusStore.setConnected(pending, detail)
                        refreshStatusPanel(extra = "اختبار الاتصال نجح. إن ظهرت «متصلة الآن» في /admin/printers فالنبض يعمل.")
                        Toast.makeText(this, "الاتصال ناجح · مهام معلّقة: $pending", Toast.LENGTH_LONG).show()
                    },
                    onFailure = { error ->
                        val msg = error.message ?: "فشل غير معروف"
                        statusStore.setFailed(msg)
                        refreshStatusPanel(extra = "فشل اختبار الاتصال.")
                        Toast.makeText(this, msg, Toast.LENGTH_LONG).show()
                    },
                )
            }
        }.start()
    }

    private fun runPrinterTest() {
        val printer = UsbThermalPrinter(this)
        if (!printer.isReady()) {
            printer.requestPermissionIfNeeded()
            refreshStatusPanel(extra = "وصل طابعة USB ووافق على الصلاحية ثم اضغط اختبار مرة أخرى.")
            Toast.makeText(this, "وصل طابعة USB ووافق على الصلاحية ثم اضغط اختبار مرة أخرى.", Toast.LENGTH_LONG).show()
        } else {
            Thread {
                val result = runCatching { printer.print(EscPosRenderer.testPage()) }
                runOnUiThread {
                    refreshStatusPanel(
                        extra = if (result.isSuccess) {
                            "تم إرسال صفحة الاختبار للطابعة."
                        } else {
                            "فشل اختبار الطابعة: ${result.exceptionOrNull()?.message}"
                        },
                    )
                    Toast.makeText(
                        this,
                        if (result.isSuccess) "تم إرسال صفحة الاختبار للطابعة." else "فشل الاختبار: ${result.exceptionOrNull()?.message}",
                        Toast.LENGTH_LONG,
                    ).show()
                }
            }.start()
        }
    }

    private fun refreshStatusPanel(extra: String? = null) {
        val snap = statusStore.snapshot()
        val usb = UsbThermalPrinter(this).describeConnectedDevice()
        val time = if (snap.updatedAtMs > 0L) {
            val fmt = SimpleDateFormat("HH:mm:ss", Locale("ar"))
            fmt.timeZone = TimeZone.getDefault()
            fmt.format(Date(snap.updatedAtMs))
        } else {
            "—"
        }
        val errorBlock = if (snap.lastError.isNotBlank()) "\nآخر خطأ: ${snap.lastError}" else ""
        val extraBlock = if (!extra.isNullOrBlank()) "\n$extra" else ""
        statusText.text = """
            الحالة: ${snap.stateLabelAr}
            التفاصيل: ${snap.detail.ifBlank { "—" }}
            المهام المعلّقة (من الخادم): ${snap.pendingJobs}
            آخر تحديث محلي: $time
            USB: $usb$errorBlock$extraBlock
        """.trimIndent()
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
        ViewGroup.LayoutParams.WRAP_CONTENT,
    )
}
