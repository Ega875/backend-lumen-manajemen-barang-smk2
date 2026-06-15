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

    // Tampilkan Isi Keranjang
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

    // Tambah Barang ke Keranjang
    public function store(Request $request)
    {
        $user = $request->auth;
        if ($tertolak = $this->batasiAksesJurusan($user)) return $tertolak;

        $this->validate($request, [
            'master_barang_id'   => 'nullable|exists:master_barang_umum,id', 
            'nama_barang_kustom' => 'nullable|string',                      
            'harga_estimasi'     => 'nullable|numeric',             
            'jumlah'             => 'required|integer|min:1',
            'spesifikasi'        => 'nullable|string' 
        ]);

        // Skenario A: Jika user memilih BARANG STANDAR dari katalog
        if ($request->master_barang_id) {
            $masterBarang = \App\Models\MasterBarangUmum::find($request->master_barang_id);
            if (!$masterBarang) {
                return response()->json(['success' => false, 'message' => 'Barang standar tidak ditemukan'], 404);
            }

            $cekKeranjang = Keranjang::where('user_id', $user->id)
                            ->where('master_barang_id', $request->master_barang_id)
                            ->first();

            if ($cekKeranjang) {
                $cekKeranjang->jumlah += $request->jumlah;
                $cekKeranjang->total_harga = $cekKeranjang->jumlah * $cekKeranjang->harga_satuan;
                $cekKeranjang->save();
                $keranjang = $cekKeranjang;
            } else {
                $keranjang = Keranjang::create([
                    'user_id'          => $user->id,
                    'master_barang_id' => $masterBarang->id,
                    'nama_barang'      => null,
                    'harga_satuan'     => $masterBarang->harga_satuan,
                    'jumlah'           => $request->jumlah,
                    'total_harga'      => $masterBarang->harga_satuan * $request->jumlah,
                    // Mengunci spesifikasi ke kolom spesifikasi_umum milik master barang
                    'keterangan'       => $masterBarang->spesifikasi_umum ?? 'Spesifikasi standar sekolah.'
                ]);
            }
        } 
        // Skenario B: Jika user KETIK SENDIRI (Input Manual)
        else {
            if (!$request->nama_barang_kustom || !$request->harga_estimasi) {
                return response()->json([
                    'success' => false,
                    'message' => 'Untuk barang kustom, nama barang dan estimasi harga wajib diisi!'
                ], 400);
            }

            $keranjang = Keranjang::create([
                'user_id'          => $user->id,
                'master_barang_id' => null,
                'nama_barang'      => $request->nama_barang_kustom,
                'harga_satuan'     => $request->harga_estimasi,
                'jumlah'           => $request->jumlah,
                'total_harga'      => $request->harga_estimasi * $request->jumlah,
                // Mengunci ke nilai spesifikasi asli hasil input manual di form frontend
                'keterangan'       => $request->spesifikasi 
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil dimasukkan ke keranjang pengajuan!',
            'data'    => $keranjang
        ], 201);
    }

    // Hapus Satu Barang dari Keranjang
    public function destroy(Request $request, $id)
    {
        $user = $request->auth;
        if ($tertolak = $this->batasiAksesJurusan($user)) return $tertolak;

        $keranjang = Keranjang::where('id', $id)->where('user_id', $user->id)->first();
        if (!$keranjang) {
            return response()->json(['success' => false, 'message' => 'Barang di keranjang tidak ditemukan'], 404);
        }

        $keranjang->delete();
        return response()->json(['success' => true, 'message' => 'Barang berhasil dihapus dari keranjang.'], 200);
    }
}