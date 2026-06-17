<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\StokMasuk; // <-- 1. WAJIB PANGGIL MODELNYA DI SINI
use Illuminate\Http\Request;

class StokMasukController extends Controller
{
    public function index()
    {
        $riwayat = StokMasuk::with('barang')->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'data'    => $riwayat
        ], 200);
    }

    public function store(Request $request, $id)
    {
        $this->validate($request, [
            'jumlah_masuk' => 'required|integer|min:1',
            'keterangan'   => 'nullable|string' // Tambahkan ini agar bisa menerima alasan input stok
        ]);

        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang inventaris tidak ditemukan!'
            ], 404);
        }

        // Tambah jumlah stok lama dengan jumlah yang baru masuk
        $barang->jumlah += $request->jumlah_masuk;
        $barang->save();

        // <-- 2. TAMBAHKAN INI UNTUK MENYIMPAN TRANSAKSI KE TABEL stok_masuk
        $riwayat = StokMasuk::create([
            'barang_id'     => $id,
            'jumlah_masuk'  => $request->jumlah_masuk,
            'tanggal_masuk' => date('Y-m-d'), // Format YYYY-MM-DD sesuai tipe date di migrasimu
            'keterangan'    => $request->input('keterangan', 'Stok Masuk Sukses')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stok barang berhasil ditambahkan & riwayat masuk sukses dicatat!',
            'data'    => [
                'barang'  => $barang,
                'riwayat' => $riwayat // Biar frontend bisa membaca detail riwayatnya
            ]
        ], 200);
    }

    public function clear()
    {
        StokMasuk::query()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Riwayat pengadaan berhasil dibersihkan!'
        ], 200);
    }
}
