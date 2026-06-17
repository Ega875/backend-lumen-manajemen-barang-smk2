<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\StokKeluar; // <-- 1. WAJIB PANGGIL MODEL INI
use Illuminate\Http\Request;

class StokKeluarController extends Controller
{
    public function index()
    {
        $riwayat = StokKeluar::with('barang')->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'data'    => $riwayat
        ], 200);
    }

    public function store(Request $request, $id)
    {
        $this->validate($request, [
            'jumlah_keluar' => 'required|integer|min:1',
            'jurusan'       => 'nullable|string', // Program keahlian/jurusan tujuan (jika disalurkan)
            'penerima'      => 'nullable|string', // Nama penerima (jika disalurkan)
            'keterangan'    => 'nullable|string' 
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

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            // 1. Kurangi jumlah stok dari gudang utama
            $barang->jumlah -= $request->jumlah_keluar;
            $barang->save();

            // 2. Jika disalurkan ke jurusan, alokasikan barang ke jurusan tujuan
            $jurusan = $request->input('jurusan');
            if ($jurusan) {
                $barangJurusan = Barang::where('kode_barang', $barang->kode_barang)
                    ->where('jurusan', $jurusan)
                    ->first();

                if ($barangJurusan) {
                    $barangJurusan->jumlah += $request->jumlah_keluar;
                    $barangJurusan->save();
                } else {
                    Barang::create([
                        'kode_barang' => $barang->kode_barang,
                        'nama_barang' => $barang->nama_barang,
                        'kategori'    => $barang->kategori,
                        'jumlah'      => $request->jumlah_keluar,
                        'kondisi'     => $barang->kondisi ?? 'Baik',
                        'lokasi'      => 'Lab ' . $jurusan,
                        'jurusan'     => $jurusan,
                    ]);
                }
            }

            // 3. Catat riwayat log keluar
            $penerima = $request->input('penerima', 'N/A');
            $keteranganDetail = $jurusan 
                ? "Disalurkan ke: " . $jurusan . ". Penerima: " . $penerima . ". Catatan: " . $request->input('keterangan', '-')
                : $request->input('keterangan', 'Stok Keluar Sukses');

            $riwayat = StokKeluar::create([
                'barang_id'      => $id,
                'jumlah_keluar'  => $request->jumlah_keluar,
                'tanggal_keluar' => date('Y-m-d H:i:s'),
                'keterangan'     => $keteranganDetail
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stok barang berhasil dikurangi & riwayat berhasil dicatat!',
                'data'    => [
                    'barang'  => $barang,
                    'riwayat' => $riwayat
                ]
            ], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clear()
    {
        StokKeluar::query()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Riwayat penyaluran berhasil dibersihkan!'
        ], 200);
    }
}
