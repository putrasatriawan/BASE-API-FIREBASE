<?php

return [
    'name' => 'Auth',

    'fonnte' => [
        'url' => env('FONNTE_URL', 'https://api.fonnte.com/send'),
        'token' => env('FONNTE_TOKEN'),
    ],

    'otp' => [
        'expired_minutes' => env('OTP_EXPIRED_MINUTES', 5),
    ],
];
