<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Kreait\Laravel\Firebase\Facades\Firebase;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/firebase-debug', function () {
    $auth = Firebase::auth();
    return response()->json([
        'status' => 'ok',
        'project' => config('firebase.default'),
        'credential' => config('firebase.projects.app.credentials'),
    ]);
});
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
