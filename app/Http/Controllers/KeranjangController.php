<?php

namespace App\Http\Controllers;

use App\Models\Keranjang;
use Illuminate\Http\Request;

class KeranjangController extends Controller
{
    // Kunci gerbang utama: Semua fungsi di keranjang ini hanya boleh dilewati oleh JURUSAN
    private function batasiAksesJurusan($user)
    {
        if ($user->role !== 'jurusan') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak! Fitur keranjang pengajuan ini hanya untuk role Jurusan.'
            ], 403);
        }
        return null;
    }

    // 1. Tampilkan Isi Keranjang (Hanya milik Jurusan yang sedang login)
    public function index(Request $request)
    {
        $user = $request->auth;
        
        if ($tertolak = $this->batasiAksesJurusan($user)) return $tertolak;

        $keranjang = Keranjang::with('masterBarangUmum')
                    ->where('user_id', $user->id)
                    ->get();

        return response()->json([
            'success' => true,
            'message' => 'Isi keranjang belanja pengajuan berhasil dimuat',
            'data'    => $keranjang
        ], 200);
    }

    // 2. Tambah Barang ke Keranjang (Mendukung Pilihan Standar ATAU Ketik Sendiri)
    public function store(Request $request)
    {
        $user = $request->auth;
        
        if ($tertolak = $this->batasiAksesJurusan($user)) return $tertolak;

        $this->validate($request, [
            'master_barang_id'   => 'nullable|exists:master_barang_umum,id', // Nullable jika user ketik sendiri
            'nama_barang_kustom' => 'nullable|string',                      // Diisi jika ketik sendiri
            'harga_estimasi'     => 'nullable|numeric',                      // Diisi jika ketik sendiri
            'jumlah'             => 'required|integer|min:1',
        ]);

        // Skenario A: Jika user memilih BARANG STANDAR dari katalog
        if ($request->master_barang_id) {
            $cekKeranjang = Keranjang::where('user_id', $user->id)
                            ->where('master_barang_id', $request->master_barang_id)
                            ->first();

            if ($cekKeranjang) {
                $cekKeranjang->jumlah += $request->jumlah;
                $cekKeranjang->save();
                $keranjang = $cekKeranjang;
            } else {
                $keranjang = Keranjang::create([
                    'user_id'          => $user->id,
                    'master_barang_id' => $request->master_barang_id,
                    'nama_barang_kustom' => null,
                    'harga_estimasi'   => 0,
                    'jumlah'           => $request->jumlah,
                    'tipe_keranjang'   => 'pengajuan'
                ]);
            }
        } 
        // Skenario B: Jika user KETIK SENDIRI karena barang tidak ada di daftar
        else {
            // Validasi tambahan agar input kustom tidak kosong
            if (!$request->nama_barang_kustom || !$request->harga_estimasi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Untuk barang kustom, nama barang dan estimasi harga wajib diisi!'
                ], 400);
            }

            $keranjang = Keranjang::create([
                'user_id'          => $user->id,
                'master_barang_id' => null,
                'nama_barang_kustom' => $request->nama_barang_kustom,
                'harga_estimasi'   => $request->harga_estimasi,
                'jumlah'           => $request->jumlah,
                'tipe_keranjang'   => 'pengajuan'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil dimasukkan ke keranjang pengajuan!',
            'data'    => $keranjang
        ], 201);
    }

    // 3. Hapus Satu Barang dari Keranjang
    public function destroy(Request $request, $id)
    {
        $user = $request->auth;
        
        if ($tertolak = $this->batasiAksesJurusan($user)) return $tertolak;

        $keranjang = Keranjang::where('id', $id)->where('user_id', $user->id)->first();

        if (!$keranjang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang di keranjang tidak ditemukan'
            ], 404);
        }

        $keranjang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil dihapus dari keranjang.'
        ], 200);
    }
}