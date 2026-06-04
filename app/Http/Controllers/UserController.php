<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Mengambil semua daftar user (Bisa dipakai oleh role Sarpras/Admin)
    public function index()
    {
        $users = User::all();
        return response()->json([
            'success' => true,
            'message' => 'Daftar semua pengguna sistem',
            'data'    => $users
        ], 200);
    }

    // Membuat/Registrasi Akun Baru (Mendukung 5 role gabungan kalian)
    public function store(Request $request)
    {
        $this->validate($request, [
            'nama_user' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'role'      => 'required|in:siswa,jurusan,sarpras,keuangan,kepsek',
            'status'    => 'required|in:aktif,nonaktif'
        ]);

        $user = User::create([
            'nama_user' => $request->nama_user,
            'email'     => $request->email,
            'password'  => Hash::make($request->password), // Enkripsi password demi keamanan database
            'role'      => $request->role,
            'status'    => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akun baru berhasil didaftarkan!',
            'data'    => $user
        ], 201);
    }

    // Melihat detail satu profil user saja
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data user tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $user
        ], 200);
    }

    // --- SISIPAN BARU: Memperbarui/Reset Password User yang Lupa oleh Sarpras ---
    public function resetPassword(Request $request, $id)
    {
        // 1. Validasi input password baru
        $this->validate($request, [
            'password' => 'required|min:6'
        ]);

        // 2. Cari user berdasarkan ID
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data user tidak ditemukan'
            ], 404);
        }

        // 3. Update password baru yang sudah di-hash
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => "Password untuk user {$user->nama_user} (Role: {$user->role}) berhasil diperbarui!"
        ], 200);
    }
    // ----------------------------------------------------------------------------
}