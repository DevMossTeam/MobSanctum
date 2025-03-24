<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure PHPMailer is installed via Composer

class SignUpController extends Controller
{
    /**
     * Step 1: Validasi input (nama lengkap, username, email) & kirim OTP
     */
    public function registerStep1(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'nama_lengkap'  => 'required|max:100',
            'nama_pengguna' => 'required|max:60',
            'email'         => 'required|email|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal!',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Cek duplikasi username atau email di tabel User
        if (User::where('nama_pengguna', $request->nama_pengguna)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Username sudah digunakan.'
            ], 422);
        }
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Email sudah digunakan.'
            ], 422);
        }

        // Generate OTP (6 digit)
        $otp = rand(100000, 999999);

        // Simpan data pendaftaran sementara di cache (10 menit)
        $pendingData = [
            'nama_lengkap'  => $request->nama_lengkap,
            'nama_pengguna' => $request->nama_pengguna,
            'email'         => $request->email,
            'otp'           => $otp,
            'verified'      => false,
        ];
        Cache::put('pending_registration_' . $request->email, $pendingData, now()->addMinutes(10));

        // Kirim OTP via email menggunakan PHPMailer dengan konfigurasi dari .env
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_USERNAME');
            $mail->Password   = env('MAIL_PASSWORD');
            $mail->SMTPSecure = env('MAIL_ENCRYPTION', 'tls');
            $mail->Port       = env('MAIL_PORT');

            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->addAddress($request->email, $request->nama_lengkap);
            $mail->Subject = 'Kode OTP Anda';
            $mail->Body    = "Kode OTP untuk pendaftaran adalah: {$otp}";
            $mail->send();
        } catch (Exception $e) {
            // Jika pengiriman email gagal, hapus data pending dan kembalikan error
            Cache::forget('pending_registration_' . $request->email);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP: ' . $mail->ErrorInfo
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP telah dikirim ke email Anda. Silakan verifikasi OTP untuk melanjutkan.'
        ], 200);
    }

    /**
     * Step 2: Verifikasi OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:100',
            'otp'   => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal!',
                'errors'  => $validator->errors()
            ], 422);
        }

        $pendingData = Cache::get('pending_registration_' . $request->email);

        if (!$pendingData) {
            return response()->json([
                'success' => false,
                'message' => 'Data pendaftaran tidak ditemukan atau telah kadaluarsa.'
            ], 404);
        }

        if ($pendingData['otp'] != $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'OTP yang dimasukkan salah.'
            ], 422);
        }

        // Tandai data pending sebagai terverifikasi
        $pendingData['verified'] = true;
        Cache::put('pending_registration_' . $request->email, $pendingData, now()->addMinutes(10));

        return response()->json([
            'success' => true,
            'message' => 'OTP berhasil diverifikasi.'
        ], 200);
    }

    /**
     * Step 3: Selesaikan Pendaftaran (input password) & masukkan ke database
     */
    public function registerStep3(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|max:100',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal!',
                'errors'  => $validator->errors()
            ], 422);
        }

        $pendingData = Cache::get('pending_registration_' . $request->email);

        if (!$pendingData) {
            return response()->json([
                'success' => false,
                'message' => 'Data pendaftaran tidak ditemukan atau telah kadaluarsa.'
            ], 404);
        }

        if (!$pendingData['verified']) {
            return response()->json([
                'success' => false,
                'message' => 'OTP belum diverifikasi.'
            ], 422);
        }

        // Generate UID dengan format 8-4-4-4-4 (total 28 karakter)
        // Menggunakan UUID bawaan dan memotong segmen terakhir menjadi 4 karakter
        $uuid = Str::uuid()->toString();
        $parts = explode('-', $uuid);
        $uid = $parts[0] . '-' . $parts[1] . '-' . $parts[2] . '-' . $parts[3] . '-' . substr($parts[4], 0, 4);

        // Buat user baru
        $user = User::create([
            'uid'           => $uid,
            'nama_pengguna' => $pendingData['nama_pengguna'],
            'email'         => $pendingData['email'],
            'nama_lengkap'  => $pendingData['nama_lengkap'],
            'password'      => Hash::make($request->password),
            'role'          => 'Pembaca',
        ]);

        // Hapus data pendaftaran sementara
        Cache::forget('pending_registration_' . $request->email);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil didaftarkan!',
            'user'    => [
                'uid'           => $user->uid,
                'nama_pengguna' => $user->nama_pengguna,
                'email'         => $user->email,
                'role'          => $user->role,
            ]
        ], 201);
    }
}
