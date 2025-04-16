<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SignInController;
use App\Http\Controllers\API\SignUpController;
use App\Http\Controllers\API\GetProfileController;
use App\Http\Controllers\API\UpdateProfileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Di sini Anda dapat mendaftarkan route API untuk aplikasi Anda. Semua route
| akan dimuat melalui RouteServiceProvider dan diberikan grup middleware "api".
|
*/

// ✅ Route untuk user terautentikasi via Sanctum
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return response()->json($request->user());
});

// ✅ Auth routes
Route::post('/login', [SignInController::class, 'login']);
Route::post('/logout', [SignInController::class, 'logout'])->middleware('auth:sanctum');

// ✅ Multi-Step Registration
Route::post('/register-step1', [SignUpController::class, 'registerStep1']);
Route::post('/verify-otp', [SignUpController::class, 'verifyOtp']);
Route::post('/register-step3', [SignUpController::class, 'registerStep3']);

// ✅ User public data
Route::get('/user', [SignUpController::class, 'getUser']);
Route::get('/user/{uid}', [SignUpController::class, 'getUserByUid']);

// ✅ Protected profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [GetProfileController::class, 'getProfile']);
    Route::post('/profile/update', [UpdateProfileController::class, 'updateProfile'])
    ->middleware('auth:sanctum');
});
