<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use Illuminate\Http\Request;

class RiwayatPengembalianController extends Controller
{
    // Fungsi khusus untuk mengambil daftar riwayat barang yang SUDAH KEMBALI
    public function index(Request $request)
    {
        $user = $request->auth;

        // Jika siswa atau jurusan yang akses, filter hanya riwayat pengembalian mereka sendiri
        if (in_array($user->role, ['siswa', 'jurusan'])) {
            $riwayat = Peminjaman::where('user_id', $user->id)
                        ->where('status_pinjam', 'kembali')
                        ->orderBy('tanggal_kembali', 'DESC')
                        ->get();
        } else {
            // Jika Sarpras yang mengakses, tampilkan semua log data pengembalian di sekolah
            $riwayat = Peminjaman::where('status_pinjam', 'kembali')
                        ->orderBy('tanggal_kembali', 'DESC')
                        ->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Data seluruh riwayat pengembalian berhasil diambil.',
            'data'    => $riwayat
        ], 200);
    }
}