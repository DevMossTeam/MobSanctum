<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SignInController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input dengan kedua field nullable
        $validator = Validator::make($request->all(), [
            'email'         => 'nullable|string',
            'nama_pengguna' => 'nullable|string',
            'password'      => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal!',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Pastikan salah satu dari email atau nama_pengguna diisi
        if (!$request->filled('email') && !$request->filled('nama_pengguna')) {
            return response()->json([
                'success' => false,
                'message' => 'Harap isi email atau nama_pengguna untuk login.'
            ], 422);
        }

        // 3. Ambil input login: prioritaskan email jika ada, jika tidak gunakan nama_pengguna
        $loginInput = $request->filled('email') ? $request->input('email') : $request->input('nama_pengguna');

        // 4. Cari user berdasarkan email atau nama_pengguna
        $user = User::where('email', $loginInput)
                    ->orWhere('nama_pengguna', $loginInput)
                    ->first();

        // Jika user tidak ditemukan
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Nama pengguna atau email tidak ditemukan.'
            ], 401);
        }

        // 5. Jika password salah
        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password yang Anda masukkan salah.'
            ], 401);
        }

        // 6. Buat token untuk API menggunakan Laravel Sanctum.
        $token = $user->createToken('auth_token')->plainTextToken;

        // 7. Kirim email notifikasi login
        try {
            $loginTime = Carbon::now()->format('Y-m-d H:i:s');
            $plaintext = <<<TEXT
Halo {$user->nama_pengguna},

Akun Anda baru saja login pada {$loginTime} dari IP {$request->ip()}.

Jika bukan Anda, segera periksa keamanan akun Anda.

Salam,
Tim Dukungan
TEXT;
            $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/></head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f4f4;">
  <center style="width:100%;padding:20px 0;">
    <div style="max-width:600px;background:#FCFCFC;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
      <div style="padding:24px;color:#333;line-height:1.5;font-size:16px;">
        <p>Hai <strong>{$user->nama_pengguna}</strong>,</p>
        <p>Akun Anda baru saja login pada <strong>{$loginTime}</strong> dari IP:<br><code style="background:#eee;padding:4px 8px;border-radius:4px;">{$request->ip()}</code></p>
        <p>Jika bukan Anda, segera periksa keamanan akun Anda.</p>
        <p>Salam,<br>Tim Dukungan</p>
      </div>
    </div>
  </center>
</body>
</html>
HTML;

            Mail::send([], [], function ($message) use ($user, $plaintext, $html) {
                $message->to($user->email, $user->nama_pengguna)
                        ->subject('Notifikasi Login Baru');

                $symfony = $message->getSymfonyMessage();
                $symfony->text($plaintext, 'utf-8');
                $symfony->html($html, 'utf-8');
            });
        } catch (\Exception $e) {
            Log::error('Gagal kirim notifikasi login: ' . $e->getMessage());
        }

        // 8. Kembalikan data user dan token
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'user'    => [
                'uid'           => $user->uid,
                'nama_pengguna' => $user->nama_pengguna,
                'email'         => $user->email,
                'role'          => $user->role,
            ],
            'token'   => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil!'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'User tidak terautentikasi.'
        ], 401);
    }
}
