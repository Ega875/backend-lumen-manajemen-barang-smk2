<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    // 1. Tampilkan Semua Data Barang Inventaris (Untuk Siswa, Sarpras, dll)
    // GET -> /api/barang
    public function index()
    {
        $barang = Barang::all();

        return response()->json([
            'success' => true,
            'message' => 'Daftar Barang Inventaris Berhasil Diambil',
            'data'    => $barang // Dipastikan dibungkus dalam key 'data' agar Vue dataFilter .filter() tidak error
        ], 200);
    }

    // 2. Fitur Tambah Barang Baru (Akses: Sarpras)
    // POST -> /api/barang
    public function store(Request $request)
    {
        // PERBAIKAN: Mengubah 'unique:barang' menjadi 'unique:barangs' (sesuaikan dengan nama tabel migrasi kamu)
        $this->validate($request, [
            'nama_barang' => 'required|string',
            'kode_barang' => 'required|string|unique:barangs,kode_barang',
            'kategori'    => 'required|string',
            'jumlah'      => 'required|integer|min:0',
            'kondisi'     => 'required|string',
            'lokasi'      => 'required|string',
        ]);

        // Simpan data barang baru ke database
        $barang = Barang::create([
            'nama_barang' => $request->nama_barang,
            'kode_barang' => $request->kode_barang,
            'kategori'    => $request->kategori,
            'jumlah'      => $request->jumlah,
            'kondisi'     => $request->kondisi,
            'lokasi'      => $request->lokasi,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Barang baru berhasil ditambahkan ke inventaris!',
            'data'    => $barang
        ], 201);
    }

    // 3. Fitur STOK MASUK (Akses: Sarpras)
    // POST -> /api/barang/{id}/stok-masuk
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

    // 4. Fitur STOK KELUAR (Akses: Sarpras)
    // POST -> /api/barang/{id}/stok-keluar
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
