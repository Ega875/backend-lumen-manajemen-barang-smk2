<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
date_default_timezone_set('Asia/Jakarta'); // Set timezone sesuai kebutuhan

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input dari user
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        // 2. Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        // 3. Cek apakah user terdaftar dan password-nya cocok
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah!'
            ], 401);
        }

        // 4. Cek apakah status akun aktif (karena ada kolom status di model User)
        if ($user->status !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan atau belum diverifikasi!'
            ], 403);
        }

        // --- SISIPAN BARU: Proteksi agar Akun Siswa tidak bisa jebol sistem Pengajuan ---
        if ($user->role === 'siswa') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak! Akun Siswa tidak diizinkan masuk ke Sistem Pengajuan Barang.'
            ], 403);
        }
        // -------------------------------------------------------------------------------

        // 5. Racik isi data (Payload) di dalam Token JWT
        $payload = [
            'iss' => "manajemen-barang-smk-api", // Nama/id pembuat token
            'sub' => $user->id,                  // Menyimpan ID user
            'iat' => time(),                     // Waktu token dibuat
            'exp' => time() + (60 * 60 * 8)      // Token kadaluwarsa dalam 8 Jam
        ];

        // Generate string token JWT menggunakan key secret dari .env
        $token = JWT::encode($payload, env('JWT_SECRET', 'rahasia_super_secure_123'), 'HS256');

        // 6. Kirim respon balik ke Frontend (Kamu atau temanmu)
        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil!',
            'token'   => $token,
            'user'    => [
                'id'        => $user->id,
                'nama_user' => $user->nama_user,
                'email'     => $user->email,
                'role'      => $user->role // Nilai ini yang akan dibaca oleh Frontend kalian
            ]
        ], 200);
    }
}