<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    // 1. Mengambil semua daftar user (Dipakai frontend untuk render tabel)
    public function index()
    {
        $users = User::all();
        return response()->json([
            'success' => true,
            'message' => 'Daftar semua pengguna sistem',
            'data'    => $users
        ], 200);
    }

    // 2. Membuat/Registrasi Akun Baru oleh Sarpras
    public function store(Request $request)
    {
        if (!$request->has('role')) {
            $request->merge(['role' => 'jurusan']);
        }
        if (!$request->has('status')) {
            $request->merge(['status' => 'aktif']);
        }

        $this->validate($request, [
            'nama_user' => 'required|string|max:255',
            'username'  => 'required|string|max:255|unique:users,username',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'role'      => 'required|in:siswa,jurusan,sarpras,keuangan,kepsek',
            'status'    => 'required|in:aktif,nonaktif',
            'jurusan'   => 'nullable|string|max:255'
        ]);

        $user = User::create([
            'nama_user' => $request->input('nama_user'),
            'username'  => $request->input('username'),
            'email'     => $request->input('email'),
            'password'  => Hash::make($request->input('password')),
            'role'      => $request->input('role'),
            'status'    => $request->input('status'),
            'jurusan'   => $request->input('jurusan'),
        ]);

        return response()->json([
            'success' => true,
            'status'  => 'success',
            'message' => 'Akun jurusan berhasil dibuat!',
            'data'    => $user
        ], 201);
    }

    // 3. Melihat detail satu profil user
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

    // 4. Menghapus Akun Operator (PERBAIKAN: Fungsi pembungkus yang sempat hilang)
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data akun tidak ditemukan!'
            ], 404);
        }

        // Mencegah akun sarpras utama terhapus secara tidak sengaja
        if ($user->role === 'sarpras') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Administrator Utama tidak diperbolehkan untuk dihapus!'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun operator berhasil dihapus dari sistem secara permanen.'
        ], 200);
    }

    // 5. Jalur Login Utama (Mendukung input Email maupun Username)
    public function login(Request $request)
    {
        $this->validate($request, [
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = filter_var($request->input('login'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $loginField => $request->input('login'),
            'password'  => $request->input('password')
        ];

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email/Username atau Password salah!'
            ], 401);
        }

        $user = auth()->user();

        if ($user->status !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Silakan hubungi admin.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'token'   => $token,
            'token_type' => 'bearer',
            'user'    => [
                'id'        => $user->id,
                'nama_user' => $user->nama_user,
                'email'     => $user->email,
                'role'      => $user->role,
            ]
        ], 200);
    }

    public function profile(Request $request)
    {
        $user = User::find($request->auth->id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data'    => $user
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = User::find($request->auth->id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.'
            ], 401);
        }

        $userId = $user->id;

        $this->validate($request, [
            'nama_user' => 'required|string|max:255',
            'username'  => 'required|string|max:255|unique:users,username,' . $userId,
            'email'     => 'required|email|unique:users,email,' . $userId,
            'password'  => 'nullable|min:6'
        ]);

        $user->nama_user = $request->input('nama_user');
        $user->username = $request->input('username');
        $user->email = $request->input('email');

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui!',
            'data'    => $user
        ], 200);
    }
}
