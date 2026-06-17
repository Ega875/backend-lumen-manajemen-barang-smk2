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
                'status'          => 'dikembalikan'
            ]);

            $pengembalian = \App\Models\Pengembalian::create([
                'peminjaman_id'     => $peminjaman->id,
                'tanggal_kembali'   => date('Y-m-d'),
                'deskripsi_kembali' => 'Kondisi: ' . ucfirst($request->kondisi_kembali) . '. Keterangan: ' . $request->keterangan_kondisi
            ]);

            // Catat riwayat pengembalian
            \App\Models\RiwayatPengembalian::create([
                'pengembalian_id'   => $pengembalian->id,
                'aktivitas'         => 'Pengembalian barang ' . $peminjaman->barang->nama_barang . ' dengan kondisi ' . ucfirst($request->kondisi_kembali) . '.',
                'tanggal_aktivitas' => date('Y-m-d')
            ]);

            // Sinkronisasi data ke tabel peminjaman_alat jika ada record yang cocok
            $peminjamanAlat = \App\Models\PeminjamanAlat::where('siswa_id', $peminjaman->user_id)
                ->where('kode_alat', $peminjaman->barang->kode_barang)
                ->where('status', 'dipinjam')
                ->first();
            if ($peminjamanAlat) {
                $peminjamanAlat->update([
                    'status' => 'dikembalikan',
                    'keterangan_kondisi' => 'Kondisi: ' . ucfirst($request->kondisi_kembali) . '. Keterangan: ' . $request->keterangan_kondisi
                ]);
            }

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
        $userId = $request->auth->id; // Ambil ID user dari token
        $role   = $request->auth->role; // Ambil role user dari token

        // Jika yang login adalah siswa, hanya tampilkan riwayat pengembalian milik dia sendiri
        if ($role === 'siswa') {
            $riwayat = Peminjaman::with(['barang', 'pengembalian'])
                                 ->where('user_id', $userId)
                                 ->where('status', 'dikembalikan')
                                 ->get();
        } else {
            // Jika Sarpras atau Jurusan, tampilkan semua data yang sudah dikembalikan
            $riwayat = Peminjaman::with(['user', 'barang', 'pengembalian'])
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
