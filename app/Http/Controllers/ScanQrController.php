<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\ScanQr; // <-- 1. WAJIB PANGGIL MODELNYA DI SINI
use Illuminate\Http\Request;

class ScanQrController extends Controller
{
    public function scan(Request $request)
    {
        $this->validate($request, [
            'kode_barang' => 'required|string'
        ]);

        // Cari data barang berdasarkan kode unik hasil scan QR
        $kodeBarangInput = strtolower(trim($request->kode_barang));

        $barang = Barang::where('kode_barang', $kodeBarangInput)->first();

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak terdaftar! QR Code tidak dikenali oleh sistem.'
            ], 404);
        }

        // Ambil ID user dari Token JWT dan simpan log ke database
        $userId = $request->auth->id;

        $logScan = ScanQr::create([
            'user_id'      => $userId,
            'barang_id'    => $barang->id,
            'tanggal_scan' => date('Y-m-d H:i:s'),
            'hasil_scan'   => 'Berhasil scan barang: ' . $barang->nama_barang
        ]);

        // Jika ditemukan, kembalikan info barangnya beserta bukti log-nya
        return response()->json([
            'success' => true,
            'message' => 'QR Code Valid! Data barang berhasil dimuat & aktivitas dicatat.',
            'data'    => [
                'id'            => $barang->id,
                'kode_barang'   => $barang->kode_barang,
                'nama_barang'   => $barang->nama_barang,
                'kategori'      => $barang->kategori,
                'kondisi'       => $barang->kondisi,
                'stok_tersedia' => $barang->jumlah, // Ini sudah benar, mencocokkan dengan kolom 'jumlah' di database
                'log_scan'      => $logScan
            ]
        ], 200);
    }
}
