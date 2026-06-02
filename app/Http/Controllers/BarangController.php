<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    // 1. Tampilkan Semua Data Barang Inventaris (Untuk Siswa & Sarpras)
    public function index()
    {
        // Temanmu butuh semua kolom untuk inventaris, jadi kita all() atau select sesuai kebutuhan
        $barang = Barang::all();
        
        return response()->json([
            'success' => true,
            'message' => 'Daftar Barang Inventaris Berhasil Diambil',
            'data'    => $barang
        ], 200);
    }

    // 2. Fitur STOK MASUK (Hanya boleh diakses oleh Sarpras)
    public function stokMasuk(Request $request, $id)
    {
        $this->validate($request, [
            'jumlah_masuk' => 'required|integer|min:1'
        ]);

        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang inventaris tidak ditemukan!'
            ], 404);
        }

        // Tambahkan jumlah stok lama dengan stok yang baru masuk
        $barang->jumlah += $request->jumlah_masuk;
        $barang->save();

        return response()->json([
            'success' => true,
            'message' => 'Stok barang berhasil ditambah (Stok Masuk)!',
            'data'    => $barang
        ], 200);
    }

    // 3. Fitur STOK KELUAR (Hanya boleh diakses oleh Sarpras, misal barang rusak/menyusut)
    public function stokKeluar(Request $request, $id)
    {
        $this->validate($request, [
            'jumlah_keluar' => 'required|integer|min:1'
        ]);

        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang inventaris tidak ditemukan!'
            ], 404);
        }

        // Cek apakah stok cukup untuk dikurangi
        if ($barang->jumlah < $request->jumlah_keluar) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal! Jumlah stok keluar melebihi stok yang tersedia saat ini.'
            ], 400);
        }

        // Kurangi jumlah stok
        $barang->jumlah -= $request->jumlah_keluar;
        $barang->save();

        return response()->json([
            'success' => true,
            'message' => 'Stok barang berhasil dikurangi (Stok Keluar)!',
            'data'    => $barang
        ], 200);
    }
}