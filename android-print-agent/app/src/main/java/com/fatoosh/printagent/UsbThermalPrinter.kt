package com.fatoosh.printagent

import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.hardware.usb.UsbConstants
import android.hardware.usb.UsbDevice
import android.hardware.usb.UsbDeviceConnection
import android.hardware.usb.UsbEndpoint
import android.hardware.usb.UsbInterface
import android.hardware.usb.UsbManager

class UsbThermalPrinter(private val context: Context) {
    private val usbManager = context.getSystemService(Context.USB_SERVICE) as UsbManager
    private var lastPermissionRequestAt = 0L

    fun requestPermissionIfNeeded(): Boolean {
        val device = findPrinterDevice() ?: return false
        if (usbManager.hasPermission(device)) return true
        if (System.currentTimeMillis() - lastPermissionRequestAt < 10000) return false
        lastPermissionRequestAt = System.currentTimeMillis()

        val permissionIntent = PendingIntent.getBroadcast(
            context,
            device.deviceId,
            Intent(ACTION_USB_PERMISSION).setPackage(context.packageName),
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_MUTABLE,
        )
        usbManager.requestPermission(device, permissionIntent)
        return false
    }

    fun isReady(): Boolean {
        val device = findPrinterDevice() ?: return false
        return usbManager.hasPermission(device) && findBulkOut(device) != null
    }

    fun describeConnectedDevice(): String {
        val device = findPrinterDevice() ?: return "لا توجد طابعة USB"
        val permission = if (usbManager.hasPermission(device)) "مصرح" else "بانتظار الإذن"
        return "VID=${device.vendorId} PID=${device.productId} · $permission · ${device.deviceName}"
    }

    fun print(bytes: ByteArray) {
        val device = findPrinterDevice() ?: error("USB printer not found")
        check(usbManager.hasPermission(device)) { "USB permission not granted" }
        val target = findBulkOut(device) ?: error("USB bulk OUT endpoint not found")
        val connection = usbManager.openDevice(device) ?: error("Unable to open USB printer")
        try {
            check(connection.claimInterface(target.first, true)) { "Unable to claim USB printer interface" }
            try {
                writeChunks(connection, target.second, bytes)
            } finally {
                runCatching { connection.releaseInterface(target.first) }
            }
        } finally {
            connection.close()
        }
    }

    private fun writeChunks(connection: UsbDeviceConnection, endpoint: UsbEndpoint, bytes: ByteArray) {
        var offset = 0
        while (offset < bytes.size) {
            val length = minOf(endpoint.maxPacketSize.coerceAtLeast(64).coerceAtMost(16 * 1024), bytes.size - offset)
            val written = connection.bulkTransfer(endpoint, bytes, offset, length, 15000)
            check(written > 0) { "USB printer write failed at byte $offset (result=$written)" }
            offset += written
        }
    }

    private fun findPrinterDevice(): UsbDevice? {
        val devices = usbManager.deviceList.values
        return devices.firstOrNull { device ->
            isPrinterClass(device) && findBulkOut(device) != null
        } ?: devices.firstOrNull { device ->
            // Fallback for Chinese Android USB Host stacks that mis-report device class.
            findBulkOut(device) != null && looksLikeThermalPrinter(device)
        } ?: devices.firstOrNull { device -> findBulkOut(device) != null }
    }

    private fun isPrinterClass(device: UsbDevice): Boolean {
        if (device.deviceClass == UsbConstants.USB_CLASS_PRINTER) return true
        return (0 until device.interfaceCount).any { index ->
            device.getInterface(index).interfaceClass == UsbConstants.USB_CLASS_PRINTER
        }
    }

    private fun looksLikeThermalPrinter(device: UsbDevice): Boolean {
        // Epson (0x04b8) and common ESC/POS OEM bridges often seen with Chinese tablets.
        val knownVendors = setOf(0x04b8, 0x0519, 0x0416, 0x0483, 0x1fc9, 0x28e9)
        return device.vendorId in knownVendors || device.productName?.contains("Printer", ignoreCase = true) == true
    }

    private fun findBulkOut(device: UsbDevice): Pair<UsbInterface, UsbEndpoint>? {
        for (interfaceIndex in 0 until device.interfaceCount) {
            val usbInterface = device.getInterface(interfaceIndex)
            for (endpointIndex in 0 until usbInterface.endpointCount) {
                val endpoint = usbInterface.getEndpoint(endpointIndex)
                if (endpoint.type == UsbConstants.USB_ENDPOINT_XFER_BULK &&
                    endpoint.direction == UsbConstants.USB_DIR_OUT
                ) {
                    return usbInterface to endpoint
                }
            }
        }
        return null
    }

    companion object {
        const val ACTION_USB_PERMISSION = "com.fatoosh.printagent.USB_PERMISSION"
    }
}
