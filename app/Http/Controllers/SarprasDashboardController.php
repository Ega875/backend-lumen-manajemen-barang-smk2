<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SarprasDashboardController extends Controller
{
    public function getStatistik()
    {
        try {
            // 1. Menghitung Total Item Aset (dari tabel barang)
            $totalBarang = DB::table('barang')->count();

            // 2. Menghitung Kuantitas Stok Masuk khusus Bulan Ini dari tabel stok_masuk
            $bulanIni = date('m');
            $tahunIni = date('Y');

            $stokMasukBulanIni = DB::table('stok_masuk')
                ->whereRaw('MONTH(tanggal_masuk) = ?', [$bulanIni])
                ->whereRaw('YEAR(tanggal_masuk) = ?', [$tahunIni])
                ->sum('jumlah_masuk') ?? 0;

            // 3. Menghitung Berapa Kali Barang Disalurkan Ke Jurusan dari tabel stok_keluar
            $distribusiJurusan = DB::table('stok_keluar')->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'totalBarang' => (int) $totalBarang,
                    'stokMasukBulanIni' => (int) $stokMasukBulanIni,
                    'distribusiJurusan' => (int) $distribusiJurusan
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data statistik Sarpras: ' . $e->getMessage()
            ], 500);
        }
    }
}
