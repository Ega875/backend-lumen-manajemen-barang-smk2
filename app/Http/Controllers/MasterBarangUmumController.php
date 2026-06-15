<?php

namespace App\Http\Controllers;

use App\Models\MasterBarangUmum;
use Illuminate\Http\Request;

class MasterBarangUmumController extends Controller
{
    // 1. Tampilkan Semua Pilihan Barang Standar (Sekarang bisa untuk Jurusan & Sarpras)
    public function index(Request $request)
    {
        // Mengambil semua data, diurutkan berdasarkan Kategori lalu Nama Barang agar rapi di tabel Sarpras dan list Jurusan
        $barang = MasterBarangUmum::orderBy('kategori', 'ASC')->orderBy('nama_barang', 'ASC')->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Daftar barang standar berhasil diambil',
            'data'    => $barang
        ], 200);
    }

    // 2. Tampilkan Detail Satu Barang Standar
    public function show(Request $request, $id)
    {
        $barang = MasterBarangUmum::find($id);

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Data barang tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail data barang standar berhasil diambil',
            'data'    => $barang
        ], 200);
    }

    // 3. TAMBAH BARANG STANDAR BARU (Khusus Sarpras)
    public function store(Request $request)
    {
        $this->validate($request, [
            'nama_barang'      => 'required|string',
            'spesifikasi_umum' => 'nullable|string',
            'kategori'         => 'required|string',
            'harga_satuan'     => 'required|numeric'
        ]);

        $barang = MasterBarangUmum::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Barang standar baru berhasil ditambahkan ke katalog!',
            'data'    => $barang
        ], 201);
    }

    // 4. EDIT BARANG STANDAR (Khusus Sarpras)
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'nama_barang'      => 'required|string',
            'spesifikasi_umum' => 'nullable|string',
            'kategori'         => 'required|string',
            'harga_satuan'     => 'required|numeric'
        ]);

        $barang = MasterBarangUmum::find($id);

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Data barang standar tidak ditemukan'
            ], 404);
        }

        $barang->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Data barang standar berhasil diperbarui oleh Sarpras!',
            'data'    => $barang
        ], 200);
    }

    // 5. HAPUS BARANG STANDAR (Khusus Sarpras)
    public function destroy($id)
    {
        $barang = MasterBarangUmum::find($id);

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Data barang standar tidak ditemukan'
            ], 404);
        }

        $barang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barang standar berhasil dihapus dari katalog.'
        ], 200);
    }
}