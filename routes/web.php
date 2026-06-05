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
    // Route untuk Register dan Login
    $router->post('register', 'UserController@store');
    $router->post('login', 'UserController@login');

    // Route yang diproteksi (Harus login dulu baru bisa diakses)
    $router->group(['middleware' => 'auth'], function () use ($router) {
        $router->get('users', 'UserController->index');
        $router->get('users/{id}', 'UserController->show');
    });
});

//----------Batas Atas Sistem Pengajuan Barang----------//

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

// =========================================================================
// 1. RUTE PUBLIK (Bisa diakses siapa saja tanpa token/login)
// =========================================================================
$router->post('/login', 'AuthController@login');

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('/barang', 'BarangController@index'); // Melihat daftar inventaris publik
});


// =========================================================================
// 2. KELOMPOK RUTE YANG WAJIB LOGIN (Terproteksi JWT)
// =========================================================================
$router->group(['prefix' => 'api', 'middleware' => 'auth'], function () use ($router) {

    // Rute Umum Dashboard & Profil
    $router->get('/profile', 'UserController@profile');
    $router->get('/dashboard-siswa', 'SiswaController@index');

    // ---------------------------------------------------------------------
    // Rute Pengajuan Barang (Hanya JURUSAN & SARPRAS)
    // ---------------------------------------------------------------------
    $router->group(['middleware' => 'auth:jurusan,sarpras'], function () use ($router) {
        $router->post('/pengajuan-barang', 'PengajuanController@store');
    });

    // ---------------------------------------------------------------------
    // Rute Khusus Manajemen Stok (Hanya SARPRAS)
    // ---------------------------------------------------------------------
    $router->group(['middleware' => 'auth:sarpras'], function () use ($router) {
        $router->post('/barang', 'BarangController@store'); // Tambah barang baru
        $router->post('/barang/{id}/stok-masuk', 'BarangController@stokMasuk'); // Update angka stok
        $router->post('/barang/{id}/stok-keluar', 'BarangController@stokKeluar'); // Update angka stok

        $router->post('/stok-masuk/{id}', 'StokMasukController@store'); // Catat riwayat stok masuk
        $router->post('/stok-keluar/{id}', 'StokKeluarController@store'); // Catat riwayat stok keluar
    });

    // ---------------------------------------------------------------------
    // Rute Modul Peminjaman & Scan (SISWA, JURUSAN, SARPRAS)
    // ---------------------------------------------------------------------
    $router->group(['middleware' => 'auth:siswa,jurusan,sarpras'], function () use ($router) {
        $router->post('/peminjaman', 'PeminjamanController@store'); // Proses meminjam barang baru
        $router->get('/peminjaman/riwayat', 'PeminjamanController@riwayat'); // Lihat riwayat pinjam

        // Fitur Scan QR (Jika nanti kamu butuh logika khusus scan)
        $router->post('/peminjaman/scan', 'PeminjamanController@scanQr');
        $router->post('/scan-qr', 'ScanQrController@scan');
    });

    // ---------------------------------------------------------------------
    // Rute Modul Pengembalian Alat (SISWA, JURUSAN, SARPRAS)
    // ---------------------------------------------------------------------
    $router->group(['middleware' => 'auth:siswa,jurusan,sarpras'], function () use ($router) {
        $router->post('/pengembalian', 'PengembalianController@store'); // Eksekusi pengembalian barang
        $router->get('/pengembalian/riwayat', 'PengembalianController@riwayatKembali'); // Lihat riwayat kembali
    });

});

    //----------Batas Bawah Sistem Tracking dan Inventaris Barang----------//
