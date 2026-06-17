<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    // Mengambil semua data barang untuk dibaca oleh select option & tabel frontend
    public function index(Request $request)
    {
        $authHeader = $request->header('Authorization');
        $jurusan = null;

        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = str_replace('Bearer ', '', $authHeader);
            try {
                $secretKey = env('JWT_SECRET', 'rahasia_super_secure_123');
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secretKey, 'HS256'));
                if (isset($decoded->role) && $decoded->role === 'siswa') {
                    $jurusan = $decoded->jurusan ?? null;
                }
            } catch (\Exception $e) {
                // Ignore parsing errors
            }
        }

        if ($jurusan) {
            $barang = Barang::where('jurusan', $jurusan)->get();
        } else {
            $barang = Barang::whereNull('jurusan')->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar semua aset logistik',
            'data'    => $barang
        ], 200);
    }

    // Menambah master barang baru (opsional jika dibutuhkan oleh Sarpras)
    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_barang' => 'required|string|unique:barang,kode_barang',
            'nama_barang' => 'required|string|max:255',
            'kategori'    => 'required|string',
            'jumlah'      => 'required|integer|min:0',
            'kondisi'     => 'required|string',
            'lokasi'      => 'required|string',
        ]);

        $barang = Barang::create([
            'kode_barang' => $request->input('kode_barang'),
            'nama_barang' => $request->input('nama_barang'),
            'kategori'    => $request->input('kategori'),
            'jumlah'      => $request->input('jumlah'),
            'kondisi'     => $request->input('kondisi'),
            'lokasi'      => $request->input('lokasi'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Aset barang baru berhasil diregistrasi ke sistem.',
            'data'    => $barang
        ], 201);
    }

    // Fitur STOK MASUK (Akses: Sarpras)
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

    // Fitur STOK KELUAR (Akses: Sarpras)
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
