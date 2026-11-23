package com.fatoosh.printagent

import android.content.Context

/** Shared prefs so the foreground service can surface status into MainActivity. */
class AgentStatusStore(context: Context) {
    private val prefs = context.applicationContext.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

    fun setConnected(pendingJobs: Int, detail: String) {
        prefs.edit()
            .putString(KEY_STATE, STATE_CONNECTED)
            .putString(KEY_DETAIL, detail)
            .putString(KEY_LAST_ERROR, "")
            .putInt(KEY_PENDING, pendingJobs.coerceAtLeast(0))
            .putLong(KEY_UPDATED_AT, System.currentTimeMillis())
            .apply()
    }

    fun setWaitingUsb(pendingJobs: Int, detail: String) {
        prefs.edit()
            .putString(KEY_STATE, STATE_WAITING_USB)
            .putString(KEY_DETAIL, detail)
            .putInt(KEY_PENDING, pendingJobs.coerceAtLeast(0))
            .putLong(KEY_UPDATED_AT, System.currentTimeMillis())
            .apply()
    }

    fun setFailed(error: String, pendingJobs: Int = prefs.getInt(KEY_PENDING, 0)) {
        prefs.edit()
            .putString(KEY_STATE, STATE_FAILED)
            .putString(KEY_DETAIL, "فشل الاتصال بالخادم")
            .putString(KEY_LAST_ERROR, error.take(500))
            .putInt(KEY_PENDING, pendingJobs.coerceAtLeast(0))
            .putLong(KEY_UPDATED_AT, System.currentTimeMillis())
            .apply()
    }

    fun setStopped(detail: String = "الخدمة متوقفة") {
        prefs.edit()
            .putString(KEY_STATE, STATE_STOPPED)
            .putString(KEY_DETAIL, detail)
            .putLong(KEY_UPDATED_AT, System.currentTimeMillis())
            .apply()
    }

    fun snapshot(): Snapshot {
        val state = prefs.getString(KEY_STATE, STATE_STOPPED).orEmpty()
        return Snapshot(
            state = state,
            stateLabelAr = when (state) {
                STATE_CONNECTED -> "متصل بالخادم"
                STATE_WAITING_USB -> "متصل بالخادم · بانتظار USB"
                STATE_FAILED -> "فشل الاتصال"
                else -> "متوقف"
            },
            detail = prefs.getString(KEY_DETAIL, "").orEmpty(),
            lastError = prefs.getString(KEY_LAST_ERROR, "").orEmpty(),
            pendingJobs = prefs.getInt(KEY_PENDING, 0),
            updatedAtMs = prefs.getLong(KEY_UPDATED_AT, 0L),
        )
    }

    data class Snapshot(
        val state: String,
        val stateLabelAr: String,
        val detail: String,
        val lastError: String,
        val pendingJobs: Int,
        val updatedAtMs: Long,
    )

    companion object {
        const val PREFS = "print-agent-status"
        const val STATE_STOPPED = "stopped"
        const val STATE_CONNECTED = "connected"
        const val STATE_WAITING_USB = "waiting_usb"
        const val STATE_FAILED = "failed"
        private const val KEY_STATE = "state"
        private const val KEY_DETAIL = "detail"
        private const val KEY_LAST_ERROR = "last_error"
        private const val KEY_PENDING = "pending_jobs"
        private const val KEY_UPDATED_AT = "updated_at"
    }
}
