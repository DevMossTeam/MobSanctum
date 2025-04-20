<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SignInController;
use App\Http\Controllers\API\SignUpController;
use App\Http\Controllers\API\GetProfileController;
use App\Http\Controllers\API\UpdateProfileController;
use App\Http\Controllers\API\SecurityController;
use App\Http\Controllers\API\PesanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Route API untuk aplikasi MediaExplant.
|
*/

// ✅ Autentikasi pengguna (Sanctum)
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

// ✅ Data pengguna publik
Route::get('/user', [SignUpController::class, 'getUser']);
Route::get('/user/{uid}', [SignUpController::class, 'getUserByUid']);

// Lupa Password
Route::post('/password/send-reset-otp',      [SecurityController::class, 'sendResetPasswordOtp']);
Route::post('/password/verify-reset-otp',    [SecurityController::class, 'verifyResetPasswordOtp']);
Route::post('/password/reset',               [SecurityController::class, 'resetPassword']);

// ✅ Kirim Pesan (tanpa login)
Route::post('/pesan', [PesanController::class, 'store']);

// ✅ Protected routes (membutuhkan token autentikasi)
Route::middleware('auth:sanctum')->group(function () {

     // Profil pengguna
     Route::get('/profile', [GetProfileController::class, 'getProfile']);
     Route::post('/profile/update', [UpdateProfileController::class, 'updateProfile']);
     Route::post('/profile/delete-image', [UpdateProfileController::class, 'deleteProfileImage']);
     Route::get('profile/check-username',    [UpdateProfileController::class, 'checkUsername']);

    // Ganti Password
    Route::post('/password/change',             [SecurityController::class, 'changePassword']);

    // Ganti Email - Step 1 (email lama)
    Route::post('/email/send-change-otp',        [SecurityController::class, 'sendChangeEmailOtp']);
    Route::post('/email/verify-old-email-otp',  [SecurityController::class, 'verifyOldEmailOtp']);

    // Ganti Email - Step 2 (email baru)
    Route::post('/email/send-new-email-otp',     [SecurityController::class, 'sendNewEmailOtp']);
    Route::post('/email/verify-new-email-otp',  [SecurityController::class, 'verifyNewEmailOtp']);
});
// php artisan serve --host=192.168.1.21 --port=8000
