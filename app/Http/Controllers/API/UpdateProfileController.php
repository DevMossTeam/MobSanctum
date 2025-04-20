<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateProfileController extends Controller
{
    /**
     * Update profil: nama_pengguna, nama_lengkap, upload baru, atau hapus foto via flag.
     * POST /api/profile/update
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terautentikasi.'
            ], 401);
        }

        // Validasi input, nama_pengguna wajib unik kecuali milik user ini sendiri
        $validator = Validator::make($request->all(), [
            'nama_pengguna'      => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('user', 'nama_pengguna')
                    ->ignore($user->uid, 'uid'),
            ],
            'nama_lengkap'       => 'nullable|string|max:255',
            'profile_pic'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'delete_profile_pic' => 'nullable|boolean',
        ], [
            'nama_pengguna.unique' => 'Nama pengguna sudah dipakai oleh user lain.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // 1) Hapus foto lama jika diminta
            if ($request->boolean('delete_profile_pic')) {
                $this->deleteProfileFileIfExists($user->profile_pic);
                $user->profile_pic = "";
            }

            // 2) Update teks
            if ($request->filled('nama_pengguna')) {
                $user->nama_pengguna = $request->input('nama_pengguna');
            }
            if ($request->filled('nama_lengkap')) {
                $user->nama_lengkap = $request->input('nama_lengkap');
            }

            // 3) Upload foto baru
            if ($request->hasFile('profile_pic')) {
                $this->deleteProfileFileIfExists($user->profile_pic);
                $file = $request->file('profile_pic');
                $path = $file->store('uploads/profile', 'public');
                $user->profile_pic = url("storage/{$path}");
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui!',
                'user'    => [
                    'uid'           => $user->uid,
                    'nama_pengguna' => $user->nama_pengguna,
                    'nama_lengkap'  => $user->nama_lengkap,
                    'email'         => $user->email,
                    'profile_pic'   => $user->profile_pic,
                    'role'          => $user->role,
                ],
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui profil.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cek ketersediaan nama_pengguna.
     * GET /api/profile/check-username?nama_pengguna=...
     */
    public function checkUsername(Request $request)
    {
        $user     = $request->user();
        $username = $request->query('nama_pengguna');

        // Pastikan parameter terkirim
        if (!$username) {
            return response()->json([
                'available' => false,
                'message'   => 'Parameter nama_pengguna diperlukan.',
            ], 400);
        }

        // Validasi unik (ignore user sendiri)
        $validator = Validator::make(
            ['nama_pengguna' => $username],
            ['nama_pengguna' => [
                'required',
                'string',
                'max:255',
                Rule::unique('user', 'nama_pengguna')
                    ->ignore($user->uid, 'uid'),
            ]]
        );

        if ($validator->fails()) {
            return response()->json([
                'available' => false,
                'message'   => 'Nama pengguna sudah dipakai.',
            ], 200);
        }

        return response()->json([
            'available' => true,
            'message'   => 'Nama pengguna tersedia.',
        ], 200);
    }

    /**
     * Hapus foto profil saja.
     * POST /api/profile/delete-image
     */
    public function deleteProfileImage(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terautentikasi.'
            ], 401);
        }

        if (empty($user->profile_pic)) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada foto profil untuk dihapus.',
                'user'    => [
                    'uid'         => $user->uid,
                    'profile_pic' => $user->profile_pic,
                ],
            ], 200);
        }

        $this->deleteProfileFileIfExists($user->profile_pic);
        $user->profile_pic = "";
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil dihapus.',
            'user'    => [
                'uid'         => $user->uid,
                'profile_pic' => $user->profile_pic,
            ],
        ], 200);
    }

    /**
     * Helper: hapus file di disk 'public' kalau ada.
     */
    private function deleteProfileFileIfExists($profilePicUrl)
    {
        if (empty($profilePicUrl)) {
            return;
        }
        $parsed       = parse_url($profilePicUrl, PHP_URL_PATH);
        $relativePath = ltrim(str_replace('/storage/', '', $parsed), '/');
        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
