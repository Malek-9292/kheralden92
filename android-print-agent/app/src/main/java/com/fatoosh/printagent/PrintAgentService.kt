package com.fatoosh.printagent

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Intent
import android.os.Build
import android.os.IBinder
import androidx.core.app.NotificationCompat
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.cancel
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.util.concurrent.TimeUnit

class PrintAgentService : Service() {
    private val serviceScope = CoroutineScope(SupervisorJob() + Dispatchers.IO)
    private var pollingJob: Job? = null
    private val http = OkHttpClient.Builder()
        .connectTimeout(12, TimeUnit.SECONDS)
        .readTimeout(25, TimeUnit.SECONDS)
        .callTimeout(30, TimeUnit.SECONDS)
        .build()

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        if (intent?.action == ACTION_STOP) {
            stopSelf()
            return START_NOT_STICKY
        }

        startForeground(NOTIFICATION_ID, buildNotification("جارٍ تجهيز اتصال الطابعة"))
        pollingJob?.cancel()
        pollingJob = serviceScope.launch { pollingLoop() }
        return START_STICKY
    }

    private suspend fun pollingLoop() {
        val prefs = getSharedPreferences("print-agent", MODE_PRIVATE)
        val server = prefs.getString("server_url", "").orEmpty().trimEnd('/')
        val printerCode = prefs.getString("printer_code", "").orEmpty()
        val token = SecureTokenStore(this).read().orEmpty()

        if (server.isBlank() || printerCode.isBlank() || token.isBlank()) {
            updateNotification("أكمل إعداد رابط الخادم ورمز الطابعة")
            stopSelf()
            return
        }

        val usb = UsbThermalPrinter(this)
        while (serviceScope.isActive) {
            try {
                if (!usb.isReady()) {
                    usb.requestPermissionIfNeeded()
                    updateNotification("في انتظار توصيل طابعة USB والسماح بالوصول")
                    delay(3000)
                    continue
                }

                // Finish any USB-success jobs whose complete ack was lost (idempotent on server).
                flushPendingCompletes(server, printerCode, token)

                updateNotification("متصل بالطابعة · في انتظار تذاكر المطبخ")
                val job = claimJob(server, printerCode, token)
                if (job == null) {
                    delay(1800)
                    continue
                }

                val jobId = job.optLong("id", -1)
                if (jobId <= 0L) {
                    delay(1800)
                    continue
                }

                // Never reprint a job this device already sent to USB.
                if (wasPrintedLocally(jobId)) {
                    runCatching { completeJobWithRetry(server, printerCode, token, jobId) }
                    continue
                }

                try {
                    val payload = job.optJSONObject("payload") ?: error("Print job payload is missing")
                    val bytes = when (job.optString("job_type")) {
                        "customer_receipt" -> EscPosRenderer.customerReceipt(payload)
                        else -> EscPosRenderer.kitchenTicket(payload)
                    }
                    usb.print(bytes)
                    markPrintedLocally(jobId)
                    completeJobWithRetry(server, printerCode, token, jobId)
                    clearPendingComplete(jobId)
                    updateNotification("تمت طباعة الطلب ${payload.optString("order_number")}")
                } catch (error: Exception) {
                    if (error is CancellationException) throw error
                    // USB may have succeeded before a late exception; only fail when not printed.
                    if (wasPrintedLocally(jobId)) {
                        rememberPendingComplete(jobId)
                        runCatching { completeJobWithRetry(server, printerCode, token, jobId) }
                        updateNotification("الطباعة تمت؛ جارٍ تأكيد المهمة على الخادم")
                    } else {
                        runCatching {
                            failJob(server, printerCode, token, jobId, error.message ?: "Unknown USB print error")
                        }
                        updateNotification("تعذر طباعة مهمة؛ ستتم إعادة المحاولة")
                        delay(2500)
                    }
                }
            } catch (error: Exception) {
                if (error is CancellationException) throw error
                updateNotification("تعذر الاتصال بالخادم أو الطابعة")
                delay(5000)
            }
        }
    }

    private suspend fun flushPendingCompletes(server: String, code: String, token: String) {
        for (jobId in pendingCompleteIds().toList()) {
            runCatching {
                completeJobWithRetry(server, code, token, jobId)
                clearPendingComplete(jobId)
            }
        }
    }

    private suspend fun claimJob(server: String, code: String, token: String): JSONObject? =
        withContext(Dispatchers.IO) {
            val response = post(server, "/api/print-agent/jobs/claim", code, token, JSONObject())
            response.optJSONObject("job")
        }

    private suspend fun completeJobWithRetry(
        server: String,
        code: String,
        token: String,
        jobId: Long,
        attempts: Int = 4,
    ) {
        var lastError: Exception? = null
        repeat(attempts) { index ->
            try {
                completeJob(server, code, token, jobId)
                clearPendingComplete(jobId)
                return
            } catch (error: Exception) {
                if (error is CancellationException) throw error
                lastError = error
                rememberPendingComplete(jobId)
                delay(800L * (index + 1))
            }
        }
        throw lastError ?: IllegalStateException("complete failed for job $jobId")
    }

    private suspend fun completeJob(server: String, code: String, token: String, jobId: Long) {
        post(server, "/api/print-agent/jobs/$jobId/complete", code, token, JSONObject())
    }

    private suspend fun failJob(server: String, code: String, token: String, jobId: Long, message: String) {
        val body = JSONObject().put("error", message.take(1900))
        post(server, "/api/print-agent/jobs/$jobId/fail", code, token, body)
    }

    private suspend fun post(
        server: String,
        path: String,
        code: String,
        token: String,
        body: JSONObject,
    ): JSONObject = withContext(Dispatchers.IO) {
        val request = Request.Builder()
            .url(server + path)
            .header("Accept", "application/json")
            .header("X-Printer-Code", code)
            .header("X-Printer-Token", token)
            .post(body.toString().toRequestBody("application/json; charset=utf-8".toMediaType()))
            .build()

        http.newCall(request).execute().use { response ->
            val text = response.body?.string().orEmpty()
            if (!response.isSuccessful) {
                error("Print server returned HTTP ${response.code}: ${text.take(300)}")
            }
            if (text.isBlank()) JSONObject() else JSONObject(text)
        }
    }

    private fun printedPrefs() = getSharedPreferences("print-agent-jobs", MODE_PRIVATE)

    private fun wasPrintedLocally(jobId: Long): Boolean =
        printedPrefs().getStringSet(KEY_PRINTED, emptySet()).orEmpty().contains(jobId.toString())

    private fun markPrintedLocally(jobId: Long) {
        val prefs = printedPrefs()
        val printed = prefs.getStringSet(KEY_PRINTED, emptySet()).orEmpty().toMutableSet()
        printed += jobId.toString()
        while (printed.size > 200) {
            printed.remove(printed.minOrNull())
        }
        prefs.edit().putStringSet(KEY_PRINTED, printed).apply()
        rememberPendingComplete(jobId)
    }

    private fun rememberPendingComplete(jobId: Long) {
        val prefs = printedPrefs()
        val pending = prefs.getStringSet(KEY_PENDING_COMPLETE, emptySet()).orEmpty().toMutableSet()
        pending += jobId.toString()
        prefs.edit().putStringSet(KEY_PENDING_COMPLETE, pending).apply()
    }

    private fun clearPendingComplete(jobId: Long) {
        val prefs = printedPrefs()
        val pending = prefs.getStringSet(KEY_PENDING_COMPLETE, emptySet()).orEmpty().toMutableSet()
        pending.remove(jobId.toString())
        prefs.edit().putStringSet(KEY_PENDING_COMPLETE, pending).apply()
    }

    private fun pendingCompleteIds(): List<Long> =
        printedPrefs().getStringSet(KEY_PENDING_COMPLETE, emptySet()).orEmpty()
            .mapNotNull { it.toLongOrNull() }
            .sorted()

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "Fatoosh Print Agent",
                NotificationManager.IMPORTANCE_LOW,
            ).apply {
                description = "حالة اتصال طابعة المطبخ"
            }
            getSystemService(NotificationManager::class.java).createNotificationChannel(channel)
        }
    }

    private fun buildNotification(message: String): Notification {
        val openApp = PendingIntent.getActivity(
            this,
            0,
            Intent(this, MainActivity::class.java),
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )
        return NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(android.R.drawable.ic_menu_info_details)
            .setContentTitle("فتوش · طباعة المطبخ")
            .setContentText(message)
            .setContentIntent(openApp)
            .setOngoing(true)
            .setOnlyAlertOnce(true)
            .build()
    }

    private fun updateNotification(message: String) {
        getSystemService(NotificationManager::class.java)
            .notify(NOTIFICATION_ID, buildNotification(message))
    }

    override fun onDestroy() {
        pollingJob?.cancel()
        serviceScope.cancel()
        super.onDestroy()
    }

    override fun onBind(intent: Intent?): IBinder? = null

    companion object {
        const val ACTION_START = "com.fatoosh.printagent.START"
        const val ACTION_STOP = "com.fatoosh.printagent.STOP"
        private const val CHANNEL_ID = "fatoosh-print-agent"
        private const val NOTIFICATION_ID = 2401
        private const val KEY_PRINTED = "printed_job_ids"
        private const val KEY_PENDING_COMPLETE = "pending_complete_ids"
    }
}
