<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UpdateProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak terautentikasi.'], 401);
        }

        // Validasi
        $validator = Validator::make($request->all(), [
            'nama_pengguna' => 'nullable|string|max:255',
            'nama_lengkap'  => 'nullable|string|max:255',
            'profile_pic'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Log untuk debugging
        \Log::info('Data request:', $request->all());

        // Update teks
        if ($request->filled('nama_pengguna')) {
            $user->nama_pengguna = $request->input('nama_pengguna');
        }
        if ($request->filled('nama_lengkap')) {
            $user->nama_lengkap = $request->input('nama_lengkap');
        }

        // Update file
        if ($request->hasFile('profile_pic')) {
            $file = $request->file('profile_pic');
            $path = $file->store('uploads/profile', 'public');
            \Log::info('File path:', ['path' => $path]);

            // Simpan URL relatif atau penuh
            $user->profile_pic = asset("storage/{$path}");
        }

        // Simpan perubahan di database
        $user->save();

        // Log data yang akan dikembalikan
        \Log::info('Updated user:', $user->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diperbarui!',
            'user'    => [
                'uid'           => $user->uid,
                'nama_pengguna' => $user->nama_pengguna,
                'nama_lengkap'  => $user->nama_lengkap,
                'email'         => $user->email,
                'profile_pic'   => $user->profile_pic,
                'role'          => $user->role,
            ],
        ], 200);
    }
}
