<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Auth Module API Routes
|--------------------------------------------------------------------------
| Prefix  : /api/auth
| Guard   : sanctum
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    Route::post('register', [AuthController::class, 'register']);
    Route::post('firebase', [AuthController::class, 'firebase']);
    Route::post('firebase-admin', [AuthController::class, 'firebaseAdmin']);

    Route::post('send-otp-wa', [AuthController::class, 'resendOtpWa']);
    Route::post('send-otp-email', [AuthController::class, 'resendOtpEmail']);
    Route::post('verify-otp-register', [AuthController::class, 'verifyOtpRegister']);

    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('forgot-password/verify-otp', [AuthController::class, 'verifyForgotPasswordOtp']);
    Route::post('forgot-password/reset', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum', 'email.verified.api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        // Session management with device support
        Route::get('sessions', [AuthController::class, 'sessions']);
        Route::delete('sessions/{tokenId}', [AuthController::class, 'revokeSession']);
        Route::post('sessions/revoke-others', [AuthController::class, 'revokeOtherSessions']);
        Route::post('sessions/refresh', [AuthController::class, 'refreshToken']);
    });
});
