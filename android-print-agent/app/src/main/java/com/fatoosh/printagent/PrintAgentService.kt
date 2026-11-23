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

class PrintAgentService : Service() {
    private val serviceScope = CoroutineScope(SupervisorJob() + Dispatchers.IO)
    private var pollingJob: Job? = null
    private val api = PrintApiClient()
    private lateinit var statusStore: AgentStatusStore

    override fun onCreate() {
        super.onCreate()
        statusStore = AgentStatusStore(this)
        createNotificationChannel()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        if (intent?.action == ACTION_STOP) {
            statusStore.setStopped("تم إيقاف الخدمة")
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
        val normalized = ServerUrl.normalize(prefs.getString("server_url", "").orEmpty())
        val server = normalized.url.orEmpty()
        val printerCode = prefs.getString("printer_code", "").orEmpty().trim().uppercase()
        val token = SecureTokenStore(this).read().orEmpty().trim()

        if (server.isBlank() || printerCode.isBlank() || token.isBlank()) {
            val msg = normalized.errorAr ?: "أكمل إعداد رابط الخادم وكود الطابعة ورمز الجهاز"
            updateNotification(msg)
            statusStore.setFailed(msg)
            stopSelf()
            return
        }

        // Persist canonical URL (no trailing slash).
        prefs.edit().putString("server_url", server).putString("printer_code", printerCode).apply()

        val usb = UsbThermalPrinter(this)
        while (serviceScope.isActive) {
            try {
                // Always heartbeat so /admin/printers shows last-seen even before USB is ready.
                val beat = api.heartbeat(server, printerCode, token)
                val pending = beat.optInt("pending_jobs", 0)

                if (!usb.isReady()) {
                    usb.requestPermissionIfNeeded()
                    val waitMsg = "متصل بالخادم · بانتظار USB والسماح بالوصول · مهام معلّقة: $pending"
                    statusStore.setWaitingUsb(pending, waitMsg)
                    updateNotification(waitMsg)
                    delay(3000)
                    continue
                }

                flushPendingCompletes(server, printerCode, token)

                val readyMsg = "متصل · USB جاهز · في انتظار تذاكر المطبخ · مهام معلّقة: $pending"
                statusStore.setConnected(pending, readyMsg)
                updateNotification(readyMsg)

                val response = api.claim(server, printerCode, token)
                val job = response.optJSONObject("job")
                val pendingAfter = response.optInt("pending_jobs", pending)
                if (job == null) {
                    statusStore.setConnected(pendingAfter, readyMsg)
                    delay(1800)
                    continue
                }

                val jobId = job.optLong("id", -1)
                if (jobId <= 0L) {
                    delay(1800)
                    continue
                }

                if (wasPrintedLocally(jobId)) {
                    runCatching { completeJobWithRetry(server, printerCode, token, jobId) }
                    continue
                }

                try {
                    val payload = job.optJSONObject("payload") ?: error("حمولة مهمة الطباعة ناقصة")
                    val bytes = when (job.optString("job_type")) {
                        "customer_receipt" -> EscPosRenderer.customerReceipt(payload)
                        else -> EscPosRenderer.kitchenTicket(payload)
                    }
                    usb.print(bytes)
                    markPrintedLocally(jobId)
                    completeJobWithRetry(server, printerCode, token, jobId)
                    clearPendingComplete(jobId)
                    val ok = "تمت طباعة الطلب ${payload.optString("order_number")}"
                    updateNotification(ok)
                    statusStore.setConnected(pendingAfter, ok)
                } catch (error: Exception) {
                    if (error is CancellationException) throw error
                    if (wasPrintedLocally(jobId)) {
                        rememberPendingComplete(jobId)
                        runCatching { completeJobWithRetry(server, printerCode, token, jobId) }
                        updateNotification("الطباعة تمت؛ جارٍ تأكيد المهمة على الخادم")
                    } else {
                        val failMsg = error.message ?: "خطأ USB غير معروف"
                        runCatching { api.fail(server, printerCode, token, jobId, failMsg) }
                        statusStore.setFailed(failMsg, pendingAfter)
                        updateNotification("تعذر طباعة مهمة؛ ستتم إعادة المحاولة")
                        delay(2500)
                    }
                }
            } catch (error: Exception) {
                if (error is CancellationException) throw error
                val msg = error.message ?: "تعذر الاتصال بالخادم"
                statusStore.setFailed(msg)
                updateNotification("فشل الاتصال: ${msg.take(80)}")
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
                api.complete(server, code, token, jobId)
                clearPendingComplete(jobId)
                return
            } catch (error: Exception) {
                if (error is CancellationException) throw error
                lastError = error
                rememberPendingComplete(jobId)
                delay(800L * (index + 1))
            }
        }
        throw lastError ?: IllegalStateException("فشل تأكيد المهمة $jobId")
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
