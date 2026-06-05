<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Barang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengembalianController extends Controller
{
    // 1. Eksekusi proses pengembalian (POST -> /api/pengembalian)
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'peminjaman_id'  => 'required|exists:peminjaman,id',
            'kondisi_kembali' => 'required|in:baik,rusak,hilang'
        ]);

        $peminjaman = Peminjaman::where('id', $request->peminjaman_id)
                                ->where('status', 'dipinjam')
                                ->first();

        if (!$peminjaman) {
            return response()->json([
                'success' => false,
                'message' => 'Data peminjaman tidak ditemukan atau barang sudah dikembalikan!'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $peminjaman->update([
                'tanggal_kembali' => date('Y-m-d H:i:s'),
                'status'          => 'dikembalikan'
            ]);

            if ($request->kondisi_kembali !== 'hilang') {
                $barang = Barang::find($peminjaman->barang_id);

                if ($barang) {
                    $barang->jumlah += $peminjaman->jumlah_pinjam;

                    if ($request->kondisi_kembali === 'rusak') {
                        $barang->kondisi = 'Rusak Sebagian';
                    }

                    $barang->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proses pengembalian alat berhasil dikonfirmasi!',
                'data'    => $peminjaman
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // 2. TAMBAHAN: Melihat riwayat pengembalian (GET -> /api/pengembalian/riwayat)
    public function riwayatKembali(Request $request): JsonResponse
    {
        $userId = $request->auth->sub; // Ambil ID user dari token
        $role   = $request->auth->role; // Ambil role user dari token

        // Jika yang login adalah siswa, hanya tampilkan riwayat pengembalian milik dia sendiri
        if ($role === 'siswa') {
            $riwayat = Peminjaman::with(['barang'])
                                 ->where('user_id', $userId)
                                 ->where('status', 'dikembalikan')
                                 ->get();
        } else {
            // Jika Sarpras atau Jurusan, tampilkan semua data yang sudah dikembalikan
            $riwayat = Peminjaman::with(['user', 'barang'])
                                 ->where('status', 'dikembalikan')
                                 ->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar riwayat pengembalian berhasil diambil.',
            'data'    => $riwayat
        ], 200);
    }
}
