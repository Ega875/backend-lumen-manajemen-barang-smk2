<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use Illuminate\Http\Request;

class RiwayatPengembalianController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->auth;

        // PERBAIKAN: Kolom 'status' => 'dikembalikan' dan user_id => 'id'
        if (in_array($user->role, ['siswa', 'jurusan'])) {
            $riwayat = \App\Models\RiwayatPengembalian::with(['pengembalian.peminjaman.barang', 'pengembalian.peminjaman.user'])
                        ->whereHas('pengembalian.peminjaman', function($query) use ($user) {
                            $query->where('user_id', $user->id);
                        })
                        ->orderBy('id', 'DESC')
                        ->get();
        } else {
            // Jika Sarpras yang mengakses, tampilkan semua log data pengembalian di sekolah
            $riwayat = \App\Models\RiwayatPengembalian::with(['pengembalian.peminjaman.barang', 'pengembalian.peminjaman.user'])
                        ->orderBy('id', 'DESC')
                        ->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Data seluruh riwayat pengembalian.',
            'data'    => $riwayat
        ], 200);
    }
}
