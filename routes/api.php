<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SignInController;
use App\Http\Controllers\API\SignUpController;
use App\Http\Controllers\API\GetProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Di sini Anda dapat mendaftarkan route API untuk aplikasi. Semua route
| akan dimuat melalui RouteServiceProvider dan diberikan grup middleware "api".
|
*/

// Route default untuk mengembalikan data user yang sudah terautentikasi.
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentikasi
Route::post('/login', [SignInController::class, 'login']);
Route::post('/logout', [SignInController::class, 'logout'])->middleware('auth:sanctum');

// Pendaftaran Multi-Step
// Step 1: Input data dasar & pengiriman OTP
Route::post('/register-step1', [SignUpController::class, 'registerStep1']);
// Step 2: Verifikasi OTP
Route::post('/verify-otp', [SignUpController::class, 'verifyOtp']);
// Step 3: Input password & pendaftaran akhir
Route::post('/register-step3', [SignUpController::class, 'registerStep3']);

// Endpoint tambahan untuk mendapatkan data user (umum)
Route::get('/user', [SignUpController::class, 'getUsers']);
Route::get('/user/{uid}', [SignUpController::class, 'getUserByUid']);

// Endpoint untuk mendapatkan data profil user yang terautentikasi
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [GetProfileController::class, 'getProfile']);
});

