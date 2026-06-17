<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PeminjamanAlat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class JurusanController extends Controller
{
    // 1. GET /api/jurusan/siswa-meminjam
    public function getSiswaMeminjam(Request $request)
    {
        $authUser = User::find($request->auth->id);
        $jurusan = $authUser ? $authUser->jurusan : null;
        
        $peminjaman = PeminjamanAlat::where('jurusan_alat', $jurusan)
            ->where('status', 'dipinjam')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $peminjaman
        ], 200);
    }

    // 2. GET /api/jurusan/siswa-kembali
    public function getSiswaKembali(Request $request)
    {
        $authUser = User::find($request->auth->id);
        $jurusan = $authUser ? $authUser->jurusan : null;
        
        $peminjaman = PeminjamanAlat::where('jurusan_alat', $jurusan)
            ->where('status', 'dikembalikan')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $peminjaman
        ], 200);
    }

    // 3. GET /api/jurusan/siswa-semua
    public function getSiswaByJurusan(Request $request)
    {
        $authUser = User::find($request->auth->id);
        $jurusan = $authUser ? $authUser->jurusan : null;
        
        $siswa = User::where('role', 'siswa')
            ->where('jurusan', $jurusan)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $siswa
        ], 200);
    }

    // 4. POST /api/jurusan/siswa
    public function storeSiswa(Request $request)
    {
        $this->validate($request, [
            'nama_user' => 'required|string|max:255',
            'username'  => 'required|string|max:255|unique:users,username',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
        ]);

        $authUser = User::find($request->auth->id);
        $jurusan = $authUser ? $authUser->jurusan : null;

        $user = User::create([
            'nama_user' => $request->input('nama_user'),
            'username'  => $request->input('username'),
            'email'     => $request->input('email'),
            'password'  => Hash::make($request->input('password')),
            'role'      => 'siswa',
            'jurusan'   => $jurusan,
            'status'    => 'aktif',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Akun siswa berhasil dibuat!',
            'data' => $user
        ], 201);
    }

    // 5. DELETE /api/jurusan/siswa/{id}
    public function destroySiswa(Request $request, $id)
    {
        $authUser = User::find($request->auth->id);
        $jurusan = $authUser ? $authUser->jurusan : null;

        $siswa = User::where('id', $id)
            ->where('role', 'siswa')
            ->where('jurusan', $jurusan)
            ->first();

        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa tidak ditemukan atau bukan bagian dari jurusan Anda.'
            ], 404);
        }

        $siswa->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun siswa berhasil dihapus.'
        ], 200);
    }

    public function clearRiwayat(Request $request)
    {
        $authUser = User::find($request->auth->id);
        $jurusan = $authUser ? $authUser->jurusan : null;

        if (!$jurusan) {
            return response()->json([
                'success' => false,
                'message' => 'Jurusan tidak terdeteksi pada akun Anda.'
            ], 400);
        }

        // Delete all records of peminjaman_alat that belong to this jurusan
        PeminjamanAlat::where('jurusan_alat', $jurusan)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Semua riwayat peminjaman dan pengembalian untuk jurusan ' . $jurusan . ' berhasil dibersihkan!'
        ], 200);
    }
}
