<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use Illuminate\Http\Request;

class RiwayatPengembalianController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->auth;

        // PERBAIKAN: Kolom 'status' => 'dikembalikan' dan user_id => 'sub'
        if (in_array($user->role, ['siswa', 'jurusan'])) {
            $riwayat = Peminjaman::with(['barang'])
                        ->where('user_id', $user->sub) // <-- Menggunakan 'sub'
                        ->where('status', 'dikembalikan') // <-- Sesuai migrasi kamu
                        ->orderBy('tanggal_kembali', 'DESC')
                        ->get();
        } else {
            // Jika Sarpras yang mengakses, tampilkan semua log data pengembalian di sekolah
            $riwayat = Peminjaman::with(['user', 'barang'])
                        ->where('status', 'dikembalikan') // <-- Sesuai migrasi kamu
                        ->orderBy('tanggal_kembali', 'DESC')
                        ->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Data seluruh riwayat pengembalian.',
            'data'    => $riwayat
        ], 200);
    }
}
