<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Exception;

class SecurityController extends Controller
{
    /**
     * Ubah password setelah memverifikasi current_password.
     * POST /password/change
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $v = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|different:current_password',
        ]);

        if ($v->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $v->errors()
            ], 422);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini tidak cocok.'
            ], 403);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah!'
        ]);
    }

    /**
     * Kirim OTP ke email user saat ini untuk konfirmasi ganti email.
     * POST /email/send-change-otp
     */
    public function sendChangeEmailOtp(Request $request)
    {
        $user = $request->user();
        $otp = random_int(100000, 999999); // Lebih aman daripada mt_rand
        Cache::put("change_email_otp_{$user->uid}", $otp, now()->addMinutes(15));

        try {
            Mail::raw("Kode OTP untuk ganti email Anda: {$otp}", function ($msg) use ($user) {
                $msg->to($user->email)
                    ->subject('OTP Ganti Email');
            });

            return response()->json([
                'success' => true,
                'message' => 'OTP telah dikirim ke email Anda.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email. Silakan coba lagi.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kirim OTP untuk reset password ke email yang diminta.
     * POST /password/send-reset-otp
     */
    public function sendResetPasswordOtp(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email' => 'required|email|exists:user,email'
        ]);

        if ($v->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $v->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $otp = random_int(100000, 999999);
        Cache::put("reset_password_otp_{$user->uid}", $otp, now()->addMinutes(15));

        try {
            Mail::raw("Kode OTP untuk reset password: {$otp}", function ($msg) use ($user) {
                $msg->to($user->email)
                    ->subject('OTP Reset Password');
            });

            return response()->json([
                'success' => true,
                'message' => 'OTP telah dikirim ke email tersebut.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
