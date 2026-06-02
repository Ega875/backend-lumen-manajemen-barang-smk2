<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class StokKeluarController extends Controller
{
    // Fungsi khusus untuk memproses stok keluar (Akses: Sarpras)
    public function store(Request $request, $id)
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

        // Validasi: Pastikan stok yang keluar tidak melebihi stok yang ada
        if ($barang->jumlah < $request->jumlah_keluar) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal! Jumlah stok keluar melebihi stok barang yang tersedia.'
            ], 400);
        }

        // Kurangi jumlah stok lama
        $barang->jumlah -= $request->jumlah_keluar;
        $barang->save();

        return response()->json([
            'success' => true,
            'message' => 'Stok barang berhasil dikurangi (Stok Keluar Sukses)!',
            'data'    => $barang
        ], 200);
    }
}