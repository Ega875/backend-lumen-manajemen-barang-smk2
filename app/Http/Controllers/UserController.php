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
}