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

    fun print(bytes: ByteArray) {
        val device = findPrinterDevice() ?: error("USB printer not found")
        check(usbManager.hasPermission(device)) { "USB permission not granted" }
        val target = findBulkOut(device) ?: error("USB bulk OUT endpoint not found")
        val connection = usbManager.openDevice(device) ?: error("Unable to open USB printer")
        try {
            check(connection.claimInterface(target.first, true)) { "Unable to claim USB printer interface" }
            writeChunks(connection, target.second, bytes)
            connection.releaseInterface(target.first)
        } finally {
            connection.close()
        }
    }

    private fun writeChunks(connection: UsbDeviceConnection, endpoint: UsbEndpoint, bytes: ByteArray) {
        var offset = 0
        while (offset < bytes.size) {
            val length = minOf(16 * 1024, bytes.size - offset)
            val written = connection.bulkTransfer(endpoint, bytes, offset, length, 15000)
            check(written > 0) { "USB printer write failed at byte $offset" }
            offset += written
        }
    }

    private fun findPrinterDevice(): UsbDevice? {
        return usbManager.deviceList.values.firstOrNull { device ->
            device.deviceClass == UsbConstants.USB_CLASS_PRINTER ||
                (0 until device.interfaceCount).any { index ->
                    device.getInterface(index).interfaceClass == UsbConstants.USB_CLASS_PRINTER
                }
        } ?: usbManager.deviceList.values.firstOrNull { device -> findBulkOut(device) != null }
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
