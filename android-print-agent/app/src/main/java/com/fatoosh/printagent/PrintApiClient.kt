package com.fatoosh.printagent

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.util.concurrent.TimeUnit

class PrintApiClient {
    private val http = OkHttpClient.Builder()
        .connectTimeout(12, TimeUnit.SECONDS)
        .readTimeout(25, TimeUnit.SECONDS)
        .callTimeout(30, TimeUnit.SECONDS)
        .build()

    suspend fun heartbeat(server: String, code: String, token: String): JSONObject =
        post(server, "/api/print-agent/heartbeat", code, token, JSONObject())

    suspend fun claim(server: String, code: String, token: String): JSONObject =
        post(server, "/api/print-agent/jobs/claim", code, token, JSONObject())

    suspend fun complete(server: String, code: String, token: String, jobId: Long): JSONObject =
        post(server, "/api/print-agent/jobs/$jobId/complete", code, token, JSONObject())

    suspend fun fail(server: String, code: String, token: String, jobId: Long, message: String): JSONObject {
        val body = JSONObject().put("error", message.take(1900))
        return post(server, "/api/print-agent/jobs/$jobId/fail", code, token, body)
    }

    private suspend fun post(
        server: String,
        path: String,
        code: String,
        token: String,
        body: JSONObject,
    ): JSONObject = withContext(Dispatchers.IO) {
        val request = Request.Builder()
            .url(server.trimEnd('/') + path)
            .header("Accept", "application/json")
            .header("X-Printer-Code", code.trim().uppercase())
            .header("X-Printer-Token", token.trim())
            .post(body.toString().toRequestBody("application/json; charset=utf-8".toMediaType()))
            .build()

        http.newCall(request).execute().use { response ->
            val text = response.body?.string().orEmpty()
            if (!response.isSuccessful) {
                throw ApiException(response.code, humanizeHttpError(response.code, text))
            }
            if (text.isBlank()) JSONObject() else JSONObject(text)
        }
    }

    private fun humanizeHttpError(code: Int, body: String): String {
        val snippet = body.replace(Regex("\\s+"), " ").take(220)
        return when (code) {
            401 -> "رمز الجهاز أو كود الطابعة غير صحيح (401)."
            403 -> "الوصول مرفوض (403). تحقق أن وحدة الطباعة مفعّلة."
            404 -> "المسار غير موجود (404). تأكد من رابط النظام بدون / زائدة غريبة."
            429 -> "طلبات كثيرة (429). انتظر ثوانٍ ثم أعد المحاولة."
            502, 503, 530 -> "النفق أو الخادم غير متاح حاليًا ($code). أعد تشغيل المعاينة أو انتظر."
            else -> "الخادم ردّ HTTP $code: $snippet"
        }
    }

    class ApiException(val httpCode: Int, message: String) : Exception(message)
}
