<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetProfileController extends Controller
{
    /**
     * Mengembalikan data profil pengguna yang terautentikasi.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile(Request $request)
    {
        // Ambil user yang sudah terautentikasi
        $user = $request->user();

        // Jika user tidak terautentikasi, kembalikan error
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terautentikasi.'
            ], 401);
        }

        // Kembalikan data profil pengguna
        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diambil!',
            'user' => [
                'uid'           => $user->uid,
                'nama_pengguna' => $user->nama_pengguna,
                'nama_lengkap'  => $user->nama_lengkap,
                'email'         => $user->email,
                'profile_pic'   => $user->profile_pic,
                'role'          => $user->role,
            ]
        ], 200);
    }
}

