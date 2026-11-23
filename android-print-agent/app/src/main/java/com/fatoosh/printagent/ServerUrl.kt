package com.fatoosh.printagent

import java.net.URI

object ServerUrl {
    data class Result(val url: String? = null, val errorAr: String? = null)

    fun normalize(raw: String): Result {
        val trimmed = raw.trim().replace(Regex("\\s+"), "")
        if (trimmed.isBlank()) {
            return Result(errorAr = "أدخل رابط النظام (مثال https://example.com)")
        }

        val withScheme = when {
            trimmed.startsWith("https://", ignoreCase = true) ||
                trimmed.startsWith("http://", ignoreCase = true) -> trimmed
            else -> "https://$trimmed"
        }

        val uri = try {
            URI(withScheme)
        } catch (_: Exception) {
            return Result(errorAr = "رابط غير صالح. انسخ الرابط كاملًا بدون مسافات.")
        }

        val scheme = uri.scheme?.lowercase()
        val host = uri.host?.trim().orEmpty()
        if (host.isBlank()) {
            return Result(errorAr = "الرابط ينقصه اسم المضيف.")
        }

        // Production/preview tunnels must be HTTPS. Cleartext only for private LAN debug hosts.
        val isPrivateLan = host == "localhost" ||
            host == "127.0.0.1" ||
            host.startsWith("10.") ||
            host.startsWith("192.168.") ||
            Regex("^172\\.(1[6-9]|2[0-9]|3[0-1])\\.").containsMatchIn(host)

        if (scheme == "http" && !isPrivateLan) {
            return Result(errorAr = "استخدم HTTPS لروابط المعاينة/الإنتاج (مثل trycloudflare). HTTP مسموح فقط لشبكة محلية.")
        }
        if (scheme != "http" && scheme != "https") {
            return Result(errorAr = "المخطط المسموح: https أو http (محلي فقط).")
        }

        val port = if (uri.port != -1) ":${uri.port}" else ""
        val path = uri.path.orEmpty().trimEnd('/')
        val normalized = "$scheme://$host$port$path".trimEnd('/')
        return Result(url = normalized)
    }
}
