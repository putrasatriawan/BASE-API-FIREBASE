<?php

namespace App\Helpers;


class GeneralHelper
{
    public static function getPaymentFeeStructure()
    {
        // Fee structure berdasarkan dokumentasi Midtrans
        return [
            // E-Wallet
            'qris' => ['type' => 'percentage', 'rate' => 0.7, 'min' => 0],
            // Virtual Account
            'bca' => ['type' => 'flat', 'rate' => 4000],
            'bni' => ['type' => 'flat', 'rate' => 4000],
            'bri' => ['type' => 'flat', 'rate' => 4000],
            'bsi' => ['type' => 'flat', 'rate' => 4000],
            'danamon' => ['type' => 'flat', 'rate' => 4000],
            'cimb' => ['type' => 'flat', 'rate' => 4000],
            'mandiri' => ['type' => 'flat', 'rate' => 4000],
            'permata' => ['type' => 'flat', 'rate' => 4000],

        ];
    }
    public static function getPaymentMethodImage($name)
    {
        // Mapping request names to actual file names and extensions
        $paymentMethodMap = [
            'bri' => ['filename' => 'briva', 'ext' => 'png'],
            'briva' => ['filename' => 'briva', 'ext' => 'png'],
            'shopeepay' => ['filename' => 'shopeepay', 'ext' => 'webp'],
            'cimb' => ['filename' => 'cimb', 'ext' => 'webp'],
            'bca' => ['filename' => 'bca', 'ext' => 'png'],
            'bni' => ['filename' => 'bni', 'ext' => 'png'],
            'gopay' => ['filename' => 'gopay', 'ext' => 'png'],
            'indomaret' => ['filename' => 'indomaret', 'ext' => 'png'],
            'mandiri' => ['filename' => 'mandiri', 'ext' => 'svg.png'],
            'permata' => ['filename' => 'permata', 'ext' => 'png'],
        ];

        // Get mapping info or use default
        $methodInfo = $paymentMethodMap[$name] ?? ['filename' => $name, 'ext' => 'png'];

        $image = env("STORAGE_BASE_URL") . "/ayomuslim/assets/payment_methods/{$methodInfo['filename']}.{$methodInfo['ext']}";

        return $image;
    }
}
