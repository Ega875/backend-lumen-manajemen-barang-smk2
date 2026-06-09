<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // 1. Matikan pengecekan foreign key agar bisa mengosongkan tabel
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        // 2. Kosongkan tabel barang
        DB::table('barang')->truncate();

        // 3. Masukkan data sesuai dengan kolom asli di migration kamu
        DB::table('barang')->insert([
            [
                'nama_barang' => 'Proyektor Epson X41',
                'kode_barang' => 'PRJ-001',
                'kategori'    => 'Elektronik',
                'jumlah'      => 5, // PERBAIKAN: Menggunakan 'jumlah' bukan 'stok'
                'kondisi'     => 'Bagus',
                'lokasi'      => 'Ruang Lab RPL',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'nama_barang' => 'Kabel HDMI 5 Meter',
                'kode_barang' => 'KBL-002',
                'kategori'    => 'Aksesoris',
                'jumlah'      => 12, // PERBAIKAN: Menggunakan 'jumlah' bukan 'stok'
                'kondisi'     => 'Bagus',
                'lokasi'      => 'Ruang Lab RPL',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]
        ]);

        // 4. Hidupkan kembali pengecekan foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
