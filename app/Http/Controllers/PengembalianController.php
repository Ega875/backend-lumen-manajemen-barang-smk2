<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Barang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengembalianController extends Controller
{
    // 1. PROSES PENGEMBALIAN BARU (Akses khusus Sarpras sebagai verifikator)
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'peminjaman_id'  => 'required|exists:peminjamans,id', // PERBAIKAN: Sesuaikan dengan nama tabel database kamu (biasanya plural 'peminjamans')
            'kondisi_kembali' => 'required|in:baik,rusak,hilang'
        ]);

        // Cari data peminjaman yang statusnya masih 'dipinjam'
        $peminjaman = Peminjaman::where('id', $request->peminjaman_id)
                                ->where('status_pinjam', 'dipinjam')
                                ->first();

        if (!$peminjaman) {
            return response()->json([
                'success' => false,
                'message' => 'Data peminjaman tidak ditemukan atau barang sudah dikembalikan sebelumnya!'
            ], 404);
        }

        // Jalankan transaksi database aman
        DB::beginTransaction();

        try {
            // A. Update status di tabel peminjaman
            $peminjaman->update([
                'tanggal_kembali' => date('Y-m-d H:i:s'),
                'status_pinjam'   => 'kembali'
            ]);

            // B. Kembalikan stok barang ke tabel inventaris (Hanya jika kondisinya 'baik' atau 'rusak')
            if ($request->kondisi_kembali !== 'hilang') {
                /** @var Barang $barang */
                $barang = Barang::find($peminjaman->barang_id);

                if ($barang) {
                    $barang->jumlah += $peminjaman->jumlah_pinjam;

                    // Jika pas kembali ternyata rusak, update kondisi barangnya di inventaris
                    if ($request->kondisi_kembali === 'rusak') {
                        $barang->kondisi = 'Rusak Sebagian';
                    }

                    $barang->save();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Proses pengembalian alat berhasil!',
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

    // 2. RIWAYAT PENGEMBALIAN (Menampilkan daftar alat yang SUDAH kembali)
    public function riwayatKembali(Request $request): JsonResponse
    {
        // PERBAIKAN LUMEN: Menggunakan $request->user() agar konsisten dengan PeminjamanController
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Antisipasi jika user tidak terdeteksi
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terautentikasi.'
            ], 401);
        }

        // Jika siswa atau jurusan, tampilkan riwayat pengembalian milik mereka sendiri
        if (in_array($user->role, ['siswa', 'jurusan'])) {
            $riwayat = Peminjaman::where('user_id', $user->id)
                        ->where('status_pinjam', 'kembali')
                        ->orderBy('tanggal_kembali', 'DESC')
                        ->get();
        } else {
            // Jika Sarpras yang melihat, tampilkan semua daftar pengembalian sekolah
            $riwayat = Peminjaman::where('status_pinjam', 'kembali')
                        ->orderBy('tanggal_kembali', 'DESC')
                        ->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar riwayat pengembalian berhasil diambil.',
            'data'    => $riwayat
        ], 200);
    }
}
