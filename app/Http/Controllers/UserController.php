<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth; // Tambahkan facade JWT

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
            'username'  => 'required|string|max:255|unique:users,username', // Ditambahkan validasi username
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'role'      => 'required|in:siswa,jurusan,sarpras,keuangan,kepsek',
            'status'    => 'required|in:aktif,nonaktif'
        ]);

        $user = User::create([
            'nama_user' => $request->nama_user,
            'username'  => $request->username, // Ditambahkan agar tersimpan
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

    public function login(Request $request)
    {
        // 1. Validasi input login
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only(['email', 'password']);

        // 2. Cek kecocokan email & password sekaligus generate token
        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah!'
            ], 401);
        }

        // 3. Ambil data user yang sedang login untuk mengecek status & role
        $user = auth()->user();

        if ($user->status !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Silakan hubungi admin.'
            ], 403);
        }

        // 4. Jika sukses, kembalikan token dan data user beserta role-nya
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'token'   => $token,
            'token_type' => 'bearer',
            'user'    => [
                'id'        => $user->id,
                'nama_user' => $user->nama_user,
                'email'     => $user->email,
                'role'      => $user->role, // Info role (siswa/sarpras/dll) muncul di sini
            ]
        ], 200);
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
}
