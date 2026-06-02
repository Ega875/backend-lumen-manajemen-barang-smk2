<?php

namespace App\Http\Controllers;

use App\Models\MasterBarangUmum;
use Illuminate\Http\Request;

class MasterBarangUmumController extends Controller
{
    // 1. Tampilkan Semua Pilihan Barang Standar (HANYA UNTUK JURUSAN)
    public function index(Request $request)
    {
        $user = $request->auth;

        // Validasi keras: Jika bukan jurusan, tolak aksesnya!
        if ($user->role !== 'jurusan') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak! Katalog barang standar hanya boleh diakses oleh Jurusan.'
            ], 403);
        }

        $barang = MasterBarangUmum::all();
        return response()->json([
            'success' => true,
            'message' => 'Daftar barang standar berhasil diambil',
            'data'    => $barang
        ], 200);
    }

    // 2. Tampilkan Detail Satu Barang Standar (HANYA UNTUK JURUSAN)
    public function show(Request $request, $id)
    {
        $user = $request->auth;

        if ($user->role !== 'jurusan') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak!'
            ], 403);
        }

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
            'nama_barang' => 'required|string',
            'spesifikasi_umum' => 'nullable|string',
            'kategori'    => 'required|string',
            'harga_satuan' => 'required|numeric'
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
            'nama_barang' => 'required|string',
            'spesifikasi_umum' => 'nullable|string',
            'kategori'    => 'required|string',
            'harga_satuan' => 'required|numeric'
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