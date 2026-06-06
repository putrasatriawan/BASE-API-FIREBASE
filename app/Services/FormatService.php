<?php

namespace App\Services;

use App\Services\DigitalOcean\DOCdnService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Str;

class FormatService
{
    public static function convertDistanceToReadable($distanceInKm)
    {
        if ($distanceInKm >= 1) {
            return number_format($distanceInKm, 2) . " KM";
        } else {
            $distanceInMeters = round($distanceInKm * 1000);
            return $distanceInMeters . " M";
        }
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

    public static function getFormattedFotoAnggota($anggota)
    {
        if (!$anggota)
            return null;

        return $anggota->foto;
    }

    public static function convertToIntegerRecursive(&$array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                self::convertToIntegerRecursive($value); // Recursive call
            } else if (is_numeric($value)) {
                $value = (int) $value; // Convert numeric string to integer
            }
        }
    }

    public static function errorResponse(\Throwable $exception, Request $request = null)
    {
        $request = $request ?? request();
        return [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'endpoint' => $request->method() . " " . $request->url(),
            'payload' => $request->all(),
        ];
    }

    public static function parseValue($value)
    {
        $value = strtolower($value);

        switch ($value) {
            case "null":
                return null;
            case "true":
                return true;
            case "false":
                return false;
            default:
                if (is_numeric($value)) {
                    return strpos($value, '.') === false ? (int) $value : (float) $value;
                }

                return $value;
        }
    }

    public static function formatPaginationFromData(LengthAwarePaginator $data)
    {
        // $arr = $data->toArray();

        $pager = [
            'first_page_url' => $data->url(1),
            'last_page_url' => $data->url($data->lastPage()),
            'next_page_url' => $data->nextPageUrl(),
            'prev_page_url' => $data->previousPageUrl(),
            'total_data' => $data->total(),
        ];

        return $pager;
    }

    public static function formatPaginationFromCollection($request, $data)
    {
        $perPage = $request->get("limit", 10); // Number of items per page
        $page = $request->get('page', 1); // Get the current page from the request (default to 1)

        // Assume $data is your collection
        $total = $data->count(); // Total number of items
        $paginatedData = $data->forPage($page, $perPage); // Paginate the collection

        $pager = [
            'current_page' => (int) $page,
            'first_page_url' => url()->current() . '?page=1',
            'last_page_url' => url()->current() . '?page=' . ceil(($total ?: 1) / $perPage),
            'next_page_url' => $page < ceil(($total ?: 1) / $perPage) ? url()->current() . '?page=' . ($page + 1) : null,
            'prev_page_url' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
            'total_data' => $total
        ];
        return [$paginatedData->values(), $pager];
    }

    public static function capitalizeFirstWord($str)
    {
        if (!is_string($str)) {
            return $str;
        }
        return Str::of($str)
            ->explode(' ')
            ->map(function ($word, $key) {
                if ($key == 0) {
                    return Str::title($word);
                }
                return $word;
                // return Str::lower($word);
            })
            ->implode(' ');
    }

    public static function formatStorageFullPath($fileNameWithPath)
    {
        // https://{$this->bucket}.{$url}/{$folder}/{$file_name}

        $DOCdnService = app(DOCdnService::class);

        return "https://{$DOCdnService->bucket}.{$DOCdnService->url}/{$fileNameWithPath}";
    }

    public static function rupiah($angka)
    {
        $hasil_rupiah = number_format($angka, 0, ',', '.');
        return $hasil_rupiah;
    }
}
