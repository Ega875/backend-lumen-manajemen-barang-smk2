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
            $riwayat = Peminjaman::with(['barang'])
                        ->where('user_id', $user->sub) // <-- Menggunakan 'sub'
                        ->orderBy('id', 'DESC')
                        ->get();
        } else {
            // Jika Sarpras yang mengakses, tampilkan seluruh riwayat beserta data user & barang
            $riwayat = Peminjaman::with(['user', 'barang'])
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
