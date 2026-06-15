<?php

/** @var \Laravel\Lumen\Routing\Router $router */

// Rute bawaan tes status server
$router->get('/', function () use ($router) {
    return response()->json([
        'status' => 'success',
        'message' => 'Lumen API Pengajuan Barang Sekolah Siap Digunakan!',
        'version' => $router->app->version()
    ]);
});

// Grup Rute API (Tempat kita menaruh endpoint database besok)
$router->group(['prefix' => 'api'], function () use ($router) {
    
    // Rute tes di dalam grup API: http://localhost:8000/api/test
    $router->get('/test', function () {
        return response()->json([
            'status' => 'connected',
            'message' => 'Koneksi ke API Server Berhasil!'
        ]);
    });

    // Route Autentikasi Utama (Akses Terbuka Umum)
    $router->post('/login', 'AuthController@login');

    // Route Manajemen User (Nanti bisa diamankan pakai middleware)
    $router->get('/users', 'UserController@index');
    $router->post('/users', 'UserController@store');
    $router->get('/users/{id}', 'UserController@show');


//----------Batas Atas Sistem Pengajuan Barang----------//

// =========================================================================
// Rute Master Barang Umum (Sistem Pengajuan)
// =========================================================================

// 1. Jalur LIHAT DATA: Boleh diakses Jurusan & Sarpras
$router->group(['middleware' => 'role:jurusan,sarpras'], function () use ($router) {
    $router->get('/master-barang', 'MasterBarangUmumController@index');
    $router->get('/master-barang/{id}', 'MasterBarangUmumController@show');
});

// 2. Jalur EKSEKUSI DATA (CRUD): Khusus Sarpras
$router->group(['middleware' => 'role:sarpras'], function () use ($router) {
    $router->post('/master-barang', 'MasterBarangUmumController@store');
    $router->put('/master-barang/{id}', 'MasterBarangUmumController@update');
    $router->delete('/master-barang/{id}', 'MasterBarangUmumController@destroy');
});

// =========================================================================
// Rute Modul Pengajuan Barang & Tracking (Sistem Pengajuan)
// =========================================================================

// 1. LIHAT DAFTAR, DETAIL & LOGS (Semua Role bisa melihat)
$router->group(['middleware' => 'role:jurusan,sarpras,keuangan,kepsek'], function () use ($router) {
    $router->get('/pengajuan', 'PengajuanController@index');
    $router->get('/pengajuan/{id}', 'PengajuanController@show');
    $router->get('/pengajuan-detail/{pengajuan_id}', 'DetailPengajuanController@getByPengajuan');
    $router->get('/pengajuan-logs/{pengajuan_id}', 'LogsStatusController@getHistory');
});

// 2. BUAT PENGAJUAN BARU (Khusus Jurusan)
$router->group(['middleware' => 'role:jurusan'], function () use ($router) {
    $router->post('/pengajuan', 'PengajuanController@store');
});

// =========================================================================
// Rute Eksekusi Persetujuan & Revisi (YANG KEMARIN BIKIN ERROR)
// =========================================================================

// Wajib dibungkus middleware gabungan agar Lumen mengenali User ID yang menekan tombol
$router->group(['middleware' => 'role:jurusan,sarpras,keuangan,kepsek'], function () use ($router) {
    
    // Jalur Update Status (ACC/Tolak/Revisi) oleh Sarpras/Keuangan/Kepsek
    $router->post('/pengajuan-status/{id}', 'PengajuanController@updateStatus');

    // Jalur Simpan Form Revisi Massal & Pengajuan Ulang oleh Jurusan
    $router->post('/pengajuan-detail/{id}', 'PengajuanController@updateDetail');

    // TAMBAHAN BARU: Jalur untuk menghapus/membatalkan berkas
    $router->delete('/pengajuan/{id}', 'PengajuanController@destroy');
    
});

// =========================================================================
// Rute Modul Keranjang Belanja (Sistem Pengajuan)
// =========================================================================

// Seluruh aktivitas keranjang dikunci total hanya untuk role JURUSAN
$router->group(['middleware' => 'role:jurusan'], function () use ($router) {
    $router->get('/keranjang', 'KeranjangController@index');
    $router->post('/keranjang', 'KeranjangController@store');
    $router->delete('/keranjang/{id}', 'KeranjangController@destroy');
});

// =========================================================================
// Rute Manajemen Akun (Khusus Sarpras / Super Admin)
// =========================================================================
$router->group(['middleware' => 'role:sarpras'], function () use ($router) {
    $router->get('/users', 'UserController@index');
    $router->post('/users', 'UserController@store');
    $router->get('/users/{id}', 'UserController@show');
    $router->put('/users/{id}', 'UserController@update');
    $router->delete('/users/{id}', 'UserController@destroy');
});

//----------Batas Bawah Sistem Pengajuan Barang----------//


    //----------Batas Atas Sistem Tracking dan Inventaris Barang----------//

    // Rute Barang Inventaris (Akses Umum untuk melihat barang yang bisa dipinjam)
    $router->get('/barang', 'BarangController@index');

    // Rute Khusus Stok Masuk & Stok Keluar (Hanya untuk Sarpras)
    $router->group(['middleware' => 'role:sarpras'], function () use ($router) {
        $router->post('/barang/{id}/stok-masuk', 'BarangController@stokMasuk');
        $router->post('/barang/{id}/stok-keluar', 'BarangController@stokKeluar');
    });

    // Rute Modul Peminjaman & Scan QR (Aktor: Siswa, Jurusan, Sarpras)
    $router->group(['middleware' => 'role:siswa,jurusan,sarpras'], function () use ($router) {
        $router->post('/peminjaman/scan', 'PeminjamanController@scanQr'); // Jalur tembak scan QR
        $router->post('/peminjaman', 'PeminjamanController@store');       // Jalur pinjam barang
        $router->get('/peminjaman/riwayat', 'PeminjamanController@riwayat'); // Jalur lihat riwayat pinjam
    });

    // Rute Modul Pengembalian Alat (Aktor: Siswa, Jurusan, Sarpras)
    $router->group(['middleware' => 'role:siswa,jurusan,sarpras'], function () use ($router) {
        // Jalur untuk melihat list apa saja yang sudah dikembalikan
        $router->get('/pengembalian/riwayat', 'PengembalianController@riwayatKembali');
        
        // Jalur eksekusi pengembalian barang (Idealnya hanya ditembak oleh pihak 'sarpras' di frontend)
        $router->post('/pengembalian', 'PengembalianController@store');
    });

    // Rute Khusus Stok Masuk (Hanya untuk Sarpras)
    $router->post('/stok-masuk/{id}', ['middleware' => 'role:sarpras', 'uses' => 'StokMasukController@store']);

    // Rute Khusus Stok Keluar (Hanya untuk Sarpras)
    $router->post('/stok-keluar/{id}', ['middleware' => 'role:sarpras', 'uses' => 'StokKeluarController@store']);

    // Rute Khusus Scan QR Code Barang
    $router->post('/scan-qr', ['middleware' => 'role:siswa,jurusan,sarpras', 'uses' => 'ScanQrController@scan']);

    // Rute Khusus Riwayat Peminjaman (Akses: Siswa, Jurusan, Sarpras)
    $router->get('/riwayat-peminjaman', ['middleware' => 'role:siswa,jurusan,sarpras', 'uses' => 'RiwayatPeminjamanController@index']);

    // Rute Khusus Riwayat Pengembalian (Akses: Siswa, Jurusan, Sarpras)
    $router->get('/riwayat-pengembalian', ['middleware' => 'role:siswa,jurusan,sarpras', 'uses' => 'RiwayatPengembalianController@index']);
    });
    //----------Batas Atas Sistem Tracking dan Inventaris Barang----------//