<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\StokKeluar; // <-- 1. WAJIB PANGGIL MODEL INI
use Illuminate\Http\Request;

class StokKeluarController extends Controller
{
    public function store(Request $request, $id)
    {
        $this->validate($request, [
            'jumlah_keluar' => 'required|integer|min:1',
            'keterangan'    => 'nullable|string' // Ditambah keterangan (opsional)
        ]);

        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang inventaris tidak ditemukan!'
            ], 404);
        }

        if ($barang->jumlah < $request->jumlah_keluar) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal! Jumlah stok keluar melebihi stok barang yang tersedia.'
            ], 400);
        }

        // Kurangi jumlah stok lama
        $barang->jumlah -= $request->jumlah_keluar;
        $barang->save();

        // <-- 2. TAMBAHKAN INI UNTUK MENCATAT RIWAYATNYA KE TABEL STOK_KELUAR
        $riwayat = StokKeluar::create([
            'barang_id'      => $id,
            'jumlah_keluar'  => $request->jumlah_keluar,
            'tanggal_keluar' => date('Y-m-d H:i:s'), // Mencatat waktu sekarang otomatis
            'keterangan'     => $request->input('keterangan', 'Stok Keluar Sukses')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stok barang berhasil dikurangi & riwayat berhasil dicatat!',
            'data'    => [
                'barang'  => $barang,
                'riwayat' => $riwayat // Menampilkan data riwayat keluar di response API
            ]
        ], 200);
    }
}
