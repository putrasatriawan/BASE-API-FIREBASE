<?php

namespace App\Http;


class ResponseFormatter
{
    protected static $response = [
        'meta' => [
            'code' => 200,
            'status' => 'success',
            'message' => null,
            'errors' => []
        ],
        'data' => null
    ];

    public static function success($data = null, $message = "Berhasil memproses data.")
    {
        self::$response['meta']['message'] = $message;
        self::$response['data'] = $data;

        return response()->json(self::$response, self::$response['meta']['code']);
    }

    public static function paginationArray($data = null, $message = null, $page = null)
    {
        self::$response['meta']['message'] = $message ?? "Data Berhasil didapatkan";
        self::$response['data'] = $data;
        self::$response['page'] = $page;
        return response()->json(self::$response, self::$response['meta']['code']);
    }

    public static function error($data = null, $message = null, $code = 400, $error = [])
    {
        // Ensure valid HTTP status code
        if ($code < 100 || $code > 599) {
            $code = 500;
        }

        self::$response['meta']['status'] = 'error';
        self::$response['meta']['code'] = $code;
        self::$response['meta']['message'] = $message;
        self::$response['meta']['errors'] = $error;
        self::$response['data'] = $data;

        return response()->json(self::$response, $code);
    }

    public static function tanggalIndonesia($tanggal)
    {
        $bulan = array(
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        );

        $var = explode('-', $tanggal);

        return $var[2] . ' ' . $bulan[(int) $var[1]] . ' ' . $var[0];
    }

    public static function formatPaginationFromData($data)
    {
        $arr = $data->toArray();

        $pager = [
            'first_page_url' => $arr['first_page_url'],
            'last_page_url' => $arr['last_page_url'],
            'next_page_url' => $arr['next_page_url'],
            'prev_page_url' => $arr['prev_page_url'],
            'total_all_data' => $arr['total'],
        ];

        return $pager;
    }
}
