<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use Illuminate\Http\Request;

class RiwayatPeminjamanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->auth; // Membaca data dari payload JWT

        // PERBAIKAN: Menggunakan $user->sub untuk ID dan menyertakan relasi 'with'
        if (in_array($user->role, ['siswa', 'jurusan'])) {
            $riwayat = \App\Models\RiwayatPeminjaman::with(['peminjaman.barang', 'peminjaman.user'])
                        ->whereHas('peminjaman', function($query) use ($user) {
                            $query->where('user_id', $user->id);
                        })
                        ->orderBy('id', 'DESC')
                        ->get();
        } else {
            // Jika Sarpras yang mengakses, tampilkan seluruh riwayat beserta data user & barang
            $riwayat = \App\Models\RiwayatPeminjaman::with(['peminjaman.barang', 'peminjaman.user'])
                        ->orderBy('id', 'DESC')
                        ->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Data seluruh riwayat peminjaman.',
            'data'    => $riwayat
        ], 200);
    }
}
