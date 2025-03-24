<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SignInController extends Controller
{
    public function login(Request $request)
    {
        // Validasi input dengan kedua field nullable
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

        // Pastikan salah satu dari email atau nama_pengguna diisi
        if (!$request->filled('email') && !$request->filled('nama_pengguna')) {
            return response()->json([
                'success' => false,
                'message' => 'Harap isi email atau nama_pengguna untuk login.'
            ], 422);
        }

        // Ambil input login: prioritaskan email jika ada, jika tidak gunakan nama_pengguna
        $loginInput = $request->filled('email') ? $request->input('email') : $request->input('nama_pengguna');

        // Cari user berdasarkan email atau nama_pengguna
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

        // Jika password salah
        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password yang Anda masukkan salah.'
            ], 401);
        }

        // Buat token untuk API menggunakan Laravel Sanctum.
        $token = $user->createToken('auth_token')->plainTextToken;

        // Kembalikan data user dan token
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
