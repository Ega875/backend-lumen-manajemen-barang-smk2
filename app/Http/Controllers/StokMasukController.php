<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class StokMasukController extends Controller
{
    // Fungsi khusus untuk memproses stok masuk (Akses: Sarpras)
    public function store(Request $request, $id)
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

        // Tambah jumlah stok lama dengan jumlah yang baru masuk
        $barang->jumlah += $request->jumlah_masuk;
        $barang->save();

        return response()->json([
            'success' => true,
            'message' => 'Stok barang berhasil ditambahkan (Stok Masuk Sukses)!',
            'data'    => $barang
        ], 200);
    }
}