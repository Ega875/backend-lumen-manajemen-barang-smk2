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

    // Route reset password, bikin akun yang dikelola oleh sarpras\
    // --- SISIPKAN RUTE BARU INI DI DALAM GRUP MIDDLEWARE AUTH ---
    $router->put('users/{id}/reset-password', 'UserController@resetPassword');
});

//-----------------------Batas Atas Sistem Pengajuan Barang-------------------

// =========================================================================
// Rute Master Barang Umum (Sistem Pengajuan)
// =========================================================================

// 1. Jalur Khusus JURUSAN: Hanya jurusan yang boleh melihat katalog pilihan barang
$router->group(['middleware' => 'role:jurusan'], function () use ($router) {
    $router->get('/master-barang', 'MasterBarangUmumController@index');
    $router->get('/master-barang/{id}', 'MasterBarangUmumController@show');
});

// 2. Jalur Khusus SARPRAS: Mengelola/CRUD isi katalog barang standar
$router->group(['middleware' => 'role:sarpras'], function () use ($router) {
    $router->post('/master-barang', 'MasterBarangUmumController@store');         // Tambah barang baru
    $router->put('/master-barang/{id}', 'MasterBarangUmumController@update');    // Edit data barang
    $router->delete('/master-barang/{id}', 'MasterBarangUmumController@destroy'); // Hapus barang dari katalog
});

// Rute Modul Pengajuan Barang (Sistem Pengajuan)

// 1. Grup untuk LIHAT DAFTAR & DETAIL (Bisa diakses oleh semua 4 role untuk review)
$router->group(['middleware' => 'role:jurusan,sarpras,keuangan,kepsek'], function () use ($router) {
    $router->get('/pengajuan', 'PengajuanController@index');
    $router->get('/pengajuan/{id}', 'PengajuanController@show');
});

// 2. Grup KELUARAN BARU: Khusus untuk PROSES CHECKOUT (Hanya boleh ditembak oleh Jurusan)
$router->group(['middleware' => 'role:jurusan'], function () use ($router) {
    $router->post('/pengajuan', 'PengajuanController@store');

    $router->put('/pengajuan-detail-revisi/{detail_id}', 'PengajuanController@revisiDetailBarang');
});

    // Rute Modul Detail Pengajuan Barang
    $router->group(['middleware' => 'role:jurusan,sarpras,keuangan,kepsek'], function () use ($router) {
        // Ambil daftar barang di dalam satu nota pengajuan
        $router->get('/pengajuan-detail/{pengajuan_id}', 'DetailPengajuanController@getByPengajuan');
        
        // Update harga/jumlah barang di dalam nota (Hanya bisa ditembak oleh Sarpras/Keuangan lewat kodingan controller)
        $router->put('/pengajuan-detail/{id}', 'DetailPengajuanController@update');
    });

    // =========================================================================
// Rute Modul Keranjang Belanja (Sistem Pengajuan)
// =========================================================================

// Seluruh aktivitas keranjang dikunci total hanya untuk role JURUSAN
$router->group(['middleware' => 'role:jurusan'], function () use ($router) {
    $router->get('/keranjang', 'KeranjangController@index');          // Lihat isi keranjang
    $router->post('/keranjang', 'KeranjangController@store');         // Tambah item (standar / kustom)
    $router->delete('/keranjang/{id}', 'KeranjangController@destroy'); // Hapus item dari keranjang
});

// =========================================================================
// Rute Modul Logs Status (Tracking & Approval)
// =========================================================================

// 1. Jalur Timeline: Bisa diakses oleh SEMUA role yang terlibat untuk cek posisi nota
$router->group(['middleware' => 'role:jurusan,sarpras,keuangan,kepsek'], function () use ($router) {
    $router->get('/pengajuan-logs/{pengajuan_id}', 'LogsStatusController@getHistory');
});

// 2. Jalur Eksekusi: DIKUNCI KETAT hanya untuk Sarpras, Keuangan, dan Kepsek
$router->group(['middleware' => 'role:sarpras,keuangan,kepsek'], function () use ($router) {
    // Diubah menggunakan PUT karena sifatnya memperbarui status pengajuan
    $router->put('/pengajuan-status/{pengajuan_id}', 'LogsStatusController@updateStatus');
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

    //----------Batas Atas Sistem Tracking dan Inventaris Barang----------//