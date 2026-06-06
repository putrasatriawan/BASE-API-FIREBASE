<?php

namespace App\Helpers;

class OrderStatusHelper
{
    // Payment Status Constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_SETTLEMENT = 'settlement';
    const PAYMENT_SUCCESS = 'success';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_EXPIRE = 'expire';
    const PAYMENT_EXPIRED = 'expired';
    const PAYMENT_CANCEL = 'cancel';
    const PAYMENT_DENY = 'deny';
    const PAYMENT_REFUND = 'refund';

    // Shipping Status Constants
    const SHIPPING_PENDING = 'pending';
    const SHIPPING_PROCESSING = 'processing';
    const SHIPPING_READY_TO_SHIP = 'ready_to_ship';
    const SHIPPING_PICKED_UP = 'picked_up';
    const SHIPPING_IN_TRANSIT = 'in_transit';
    const SHIPPING_DELIVERED = 'delivered';
    const SHIPPING_DONE = 'done';
    const SHIPPING_CANCELLED = 'cancelled';
    const SHIPPING_RETURNED = 'returned';

    // Combined Status Labels
    const STATUS_MENUNGGU_PEMBAYARAN = 'Menunggu Pembayaran';
    const STATUS_PESANAN_DISIAPKAN = 'Pesanan Disiapkan';
    const STATUS_MENUNGGU_KURIR = 'Menunggu Kurir';
    const STATUS_PESANAN_DIKIRIM = 'Pesanan Dikirim';
    const STATUS_PESANAN_DITERIMA = 'Pesanan Diterima';
    const STATUS_SELESAI = 'Selesai';
    const STATUS_PEMBAYARAN_KADALUARSA = 'Pembayaran Kadaluarsa';
    const STATUS_PESANAN_DIBATALKAN = 'Pesanan Dibatalkan';

    /**
     * Get payment status label in Indonesian
     *
     * @param string $status
     * @return string
     */
    public static function getPaymentStatusLabel(string $status): string
    {
        $labels = [
            self::PAYMENT_PENDING => 'Menunggu Pembayaran',
            self::PAYMENT_EXPIRE => 'Pembayaran Kadaluarsa',
            self::PAYMENT_EXPIRED => 'Pembayaran Kadaluarsa',
            self::PAYMENT_SUCCESS => 'Sudah Dibayar',
            self::PAYMENT_SETTLEMENT => 'Sudah Dibayar',
            self::PAYMENT_CANCEL => 'Dibatalkan',
            self::PAYMENT_DENY => 'Ditolak',
            self::PAYMENT_FAILED => 'Gagal',
            self::PAYMENT_REFUND => 'Dikembalikan',
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Get shipping status label in Indonesian
     *
     * @param string $status
     * @return string
     */
    public static function getShippingStatusLabel(string $status): string
    {
        $labels = [
            self::SHIPPING_PENDING       => 'Menunggu Pembayaran',
            self::SHIPPING_PROCESSING    => 'Sedang Diproses',
            self::SHIPPING_READY_TO_SHIP => 'Siap Dikirim',
            self::SHIPPING_PICKED_UP     => 'Sudah Dipickup',
            self::SHIPPING_IN_TRANSIT    => 'Dalam Perjalanan',
            self::SHIPPING_DELIVERED     => 'Sudah Diterima',
            self::SHIPPING_DONE          => 'Selesai',
            self::SHIPPING_CANCELLED     => 'Dibatalkan',
            self::SHIPPING_RETURNED      => 'Dikembalikan',
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Get combined status label based on payment and shipping status
     *
     * @param string $paymentStatus
     * @param string $shippingStatus
     * @return string
     */
    public static function getCombinedStatusLabel(string $paymentStatus, string $shippingStatus): string
    {
        // Menunggu Pembayaran
        if ($paymentStatus === self::PAYMENT_PENDING) {
            return self::STATUS_MENUNGGU_PEMBAYARAN;
        }

        // Pembayaran Kadaluarsa
        if (in_array($paymentStatus, [self::PAYMENT_EXPIRE, self::PAYMENT_EXPIRED])) {
            return self::STATUS_PEMBAYARAN_KADALUARSA;
        }

        // Pesanan Dibatalkan
        if (
            in_array($paymentStatus, [self::PAYMENT_CANCEL, self::PAYMENT_FAILED, self::PAYMENT_DENY])
            || $shippingStatus === self::SHIPPING_CANCELLED
        ) {
            return self::STATUS_PESANAN_DIBATALKAN;
        }

        // Setelah pembayaran berhasil, cek shipping status
        if ($paymentStatus === self::PAYMENT_SETTLEMENT) {
            switch ($shippingStatus) {
                case self::SHIPPING_PENDING:
                    return self::STATUS_PESANAN_DISIAPKAN;

                case self::SHIPPING_PROCESSING:
                case self::SHIPPING_READY_TO_SHIP:
                    return self::STATUS_MENUNGGU_KURIR;

                case self::SHIPPING_PICKED_UP:
                case self::SHIPPING_IN_TRANSIT:
                    return self::STATUS_PESANAN_DIKIRIM;

                case self::SHIPPING_DELIVERED:
                    return self::STATUS_PESANAN_DITERIMA;

                case self::SHIPPING_DONE:
                    return self::STATUS_SELESAI;

                case self::SHIPPING_CANCELLED:
                    return self::STATUS_PESANAN_DIBATALKAN;

                default:
                    return self::STATUS_PESANAN_DISIAPKAN;
            }
        }

        // Default fallback
        return self::STATUS_MENUNGGU_PEMBAYARAN;
    }

    /**
     * Check if payment status is paid
     *
     * @param string $status
     * @return bool
     */
    public static function isPaid(string $status): bool
    {
        return in_array($status, [self::PAYMENT_SETTLEMENT, self::PAYMENT_SUCCESS]);
    }

    /**
     * Check if payment status is failed/cancelled
     *
     * @param string $status
     * @return bool
     */
    public static function isPaymentFailed(string $status): bool
    {
        return in_array($status, [
            self::PAYMENT_FAILED,
            self::PAYMENT_CANCEL,
            self::PAYMENT_DENY,
            self::PAYMENT_EXPIRE,
            self::PAYMENT_EXPIRED
        ]);
    }

    /**
     * Check if order is in shipping process
     *
     * @param string $status
     * @return bool
     */
    public static function isInShipping(string $status): bool
    {
        return in_array($status, [
            self::SHIPPING_PROCESSING,
            self::SHIPPING_READY_TO_SHIP,
            self::SHIPPING_PICKED_UP,
            self::SHIPPING_IN_TRANSIT
        ]);
    }

    /**
     * Check if order is completed
     *
     * @param string $status
     * @return bool
     */
    public static function isCompleted(string $status): bool
    {
        return in_array($status, [self::SHIPPING_DELIVERED, self::SHIPPING_DONE]);
    }

    /**
     * Check if order is cancelled
     *
     * @param string $status
     * @return bool
     */
    public static function isCancelled(string $status): bool
    {
        return $status === self::SHIPPING_CANCELLED;
    }
}
