<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

class GeneralServices
{
    public static function convertDatetime($datetime)
    {
        return Carbon::createFromTimestamp(strtotime($datetime))->toAtomString();
    }

    public static function timeElapsed($datetime, $full = false)
    {
        $now = new \DateTime;
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'tahun',
            'm' => 'bulan',
            'w' => 'minggu',
            'd' => 'hari',
            'h' => 'jam',
            'i' => 'menit',
            's' => 'detik',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v;
            } else {
                unset($string[$k]);
            }
        }

        if (!$full)
            $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' yang lalu' : null;
    }

    public static function handleStringBoolean($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (empty($value)) {
            return false;
        }

        if ($value === 'true')
            return true;
        if ($value === 'false')
            return false;

        return $value;
    }

    public static function convertDistanceToReadable($distanceInKm)
    {
        if ($distanceInKm >= 1) {
            // If distance is 1 kilometer or more, display in kilometers
            $formattedDistance = number_format($distanceInKm, 2) . " KM";
        } else {
            // If distance is less than 1 kilometer, convert to meters and round to the nearest whole number
            $distanceInMeters = round($distanceInKm * 1000);
            $formattedDistance = $distanceInMeters . " M";
        }

        return $formattedDistance;
    }

    public static function formatNumberLeadingZeros($number, $length = 3)
    {
        // Use str_pad to add leading zeros to the number
        return str_pad($number, $length, '0', STR_PAD_LEFT);
    }

    public static function removeNumParenthesis($string)
    {
        return preg_replace('/\d+\)/', '', $string);
    }

    public static function gregorianToHijria($date)
    {
        $y = date("Y", strtotime($date));
        $m = date("m", strtotime($date));
        $d = date("d", strtotime($date));
        $jd = gregoriantojd($m, $d, $y);
        $l = $jd - 1948440 + 10632;
        $n = (int) (($l - 1) / 10631);
        $l = $l - 10631 * $n + 354;
        $j = ((int) ((10985 - $l) / 5316)) * ((int) ((50 * $l) / 17719)) + (
            (int) ($l / 5670)) * ((int) ((43 * $l) / 15238));
        $l = $l - ((int) ((30 - $j) / 15)) * ((int) ((17719 * $j) / 50)) - (
            (int) ($j / 16)) * ((int) ((15238 * $j) / 43)) + 29;
        $m = (int) ((24 * $l) / 709);
        $d = $l - (int) ((709 * $m) / 24);
        $y = 30 * $n + $j - 30;

        return "$y-$m-$d";
    }

    public static function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public static function isValidHttpCode($code)
    {
        return is_numeric($code) && $code >= 100 && $code <= 599;
    }

    public static function isContainsAppName($string)
    {
        function checking($chars, $text)
        {
            $text = str_replace("@", "", $text);
            $text = strtolower($text);

            foreach ($chars as $key => $vokal) {
                $text = str_replace($key, $vokal, $text);
            }

            // $pattern = '/ayo[\s0-9]*musl[0-9]*im/i';
            $pattern = '/Ayo[\s0-9]*Muslim/i';
            return preg_match($pattern, $text) === 1;
        }


        $chars = self::numToChar();
        if (is_array($string)) {
            foreach ($string as $text) {
                if (checking($chars, $text) === true) {
                    return true;
                }
            }
        } else {
            return checking($chars, $string);
        }

        return false;
    }

    public static function numToChar(): array
    {
        return [
            '1' => 'i',
            '3' => 'e',
            '4' => 'a',
            '0' => 'o',
            '9' => 'g',
            '5' => 's',
            '6' => 'g',
            '7' => 'j',
            '8' => 'b'
        ];
    }

    public static function replaceCountryCode($phoneNumber, $replacement = "08")
    {
        $countryCodes = array(
            "628" => 3, // Indonesia
            "82" => 2,   // South Korea
            // Add more country codes as needed
        );

        foreach ($countryCodes as $code => $length) {
            if (substr($phoneNumber, 0, $length) == $code) {
                return $replacement . substr($phoneNumber, $length);
            }
        }

        return $phoneNumber; // No matching country code found
    }

    public static function translateToArabicNumber($number)
    {
        $englishNumbers = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $arabicNumbers = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');

        $arabicNumber = str_replace($englishNumbers, $arabicNumbers, $number);
        return $arabicNumber;
    }

    public static function calculateDeadline($date)
    {
        $now = Carbon::now();
        $deadlineDate = Carbon::parse($date);

        // Calculate the difference in days
        $difference = $deadlineDate->diffInDays($now);

        // Determine the format based on the difference
        if (in_array($difference, [0, 1], true)) {
            return '1 hari lagi';
        } else {
            return $difference . ' hari lagi';
        }
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

    public static function errorResponse(\Throwable $exception, Request $request = null)
    {
        $request = $request ?? request();
        return [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'endpoint' => $request->method() . " " . $request->url(),
        ];
    }

    public static function getCurrentMilliseconds()
    {
        return round(microtime(true) * 1000);
    }
}
