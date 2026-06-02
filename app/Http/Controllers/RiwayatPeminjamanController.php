<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use Illuminate\Http\Request;

class RiwayatPeminjamanController extends Controller
{
    // Fungsi khusus untuk mengambil daftar semua riwayat peminjaman
    public function index(Request $request)
    {
        $user = $request->auth;

        // Jika yang login adalah siswa atau jurusan, tampilkan riwayat milik mereka sendiri saja
        if (in_array($user->role, ['siswa', 'jurusan'])) {
            $riwayat = Peminjaman::where('user_id', $user->id)
                        ->orderBy('id', 'DESC')
                        ->get();
        } else {
            // Jika Sarpras yang mengakses, tampilkan seluruh riwayat peminjaman di sekolah
            $riwayat = Peminjaman::orderBy('id', 'DESC')->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Data seluruh riwayat peminjaman berhasil diambil.',
            'data'    => $riwayat
        ], 200);
    }
}