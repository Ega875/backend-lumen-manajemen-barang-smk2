<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Barang;
use Illuminate\Http\Request;

class PeminjamanController extends Controller
{
    // 1. SCAN QR CODE (Mencari barang berdasarkan kode unik hasil scan)
    public function scanQr(Request $request)
    {
        $this->validate($request, [
            'kode_barang' => 'required|string'
        ]);

        // Cari barang di tabel inventaris yang kode_barang-nya cocok
        $barang = Barang::where('kode_barang', $request->kode_barang)->first();

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak dikenali! QR Code tidak valid.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'QR Code Berhasil Di-scan! Data barang ditemukan.',
            'data'    => [
                'id'          => $barang->id,
                'nama_barang' => $barang->nama_barang,
                'kategori'    => $barang->kategori,
                'kondisi'     => $barang->kondisi,
                'stok_tersedia' => $barang->jumlah // Menampilkan sisa stok alat yang bisa dipinjam
            ]
        ], 200);
    }

    // 2. PROSES PEMINJAMAN BARU/ALAT
    public function store(Request $request)
    {
        $user = $request->auth; // Ambil data siswa/jurusan yang login

        $this->validate($request, [
            'barang_id'     => 'required|exists:barang,id',
            'jumlah_pinjam' => 'required|integer|min:1',
            'keperluan'     => 'required|string'
        ]);

        $barang = Barang::find($request->barang_id);

        // Validasi stok: Apakah alat yang mau dipinjam mencukupi?
        if ($barang->jumlah < $request->jumlah_pinjam) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal meminjam! Stok alat yang tersedia tidak mencukupi.'
            ], 400);
        }

        // Jalankan peminjaman (Kurangi stok barang langsung)
        $barang->jumlah -= $request->jumlah_pinjam;
        $barang->save();

        // Buat data nota peminjaman
        $peminjaman = Peminjaman::create([
            'user_id'          => $user->id,
            'barang_id'        => $request->barang_id,
            'jumlah_pinjam'    => $request->jumlah_pinjam,
            'tanggal_pinjam'   => date('Y-m-d H:i:s'),
            'tanggal_kembali'  => null, // Null karena belum dikembalikan
            'status_pinjam'    => 'dipinjam', // Status awal langsung aktif dipinjam
            'keperluan'        => $request->keperluan
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Peminjaman alat berhasil diproses! Stok terpotong.',
            'data'    => $peminjaman
        ], 21);
    }

    // 3. RIWAYAT PEMINJAMAN
    public function riwayat(Request $request)
    {
        $user = $request->auth;

        // Jika yang login adalah siswa atau jurusan, tampilkan riwayat pribadi mereka saja
        if (in_array($user->role, ['siswa', 'jurusan'])) {
            $riwayat = Peminjaman::where('user_id', $user->id)
                        ->orderBy('id', 'DESC')
                        ->get();
        } else {
            // Jika Sarpras yang mengakses, tampilkan semua riwayat peminjaman di sekolah
            $riwayat = Peminjaman::orderBy('id', 'DESC')->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar riwayat peminjaman berhasil diambil',
            'data'    => $riwayat
        ], 200);
    }
}