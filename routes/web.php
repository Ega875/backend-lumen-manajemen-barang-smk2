<?php

/** @var \Laravel\Lumen\Routing\Router $router */

// =========================================================================
// Rute Bawaan & Status Utama
// =========================================================================
$router->get('/', function () use ($router) {
    return response()->json([
        'status' => 'success',
        'message' => 'Lumen API Sistem Gabungan (Pengajuan & Inventaris) Siap Digunakan!',
        'version' => $router->app->version()
    ]);
});

// =========================================================================
// 1. KELOMPOK RUTE PUBLIK (BISA DIAKSES TANPA TOKEN / LOGIN)
// =========================================================================
$router->group(['prefix' => 'api'], function () use ($router) {

    // Jalur resmi login dan register (Gunakan AuthController sesuai frontend)
    $router->post('register', 'UserController@store');
    $router->post('login', 'AuthController@login');

    // Melihat daftar inventaris secara publik
    $router->get('barang', 'BarangController@index');
});

// =========================================================================
// 2. KELOMPOK RUTE TERPROTEKSI (WAJIB LOGIN - JWT MIDDLEWARE AUTH)
// =========================================================================
$router->group(['prefix' => 'api', 'middleware' => 'auth'], function () use ($router) {

    // --- Rute Pengguna Umum ---
    $router->get('users', 'UserController@index');
    $router->get('users/{id}', 'UserController@show');
    $router->get('profile', 'UserController@profile');
    $router->get('dashboard-siswa', 'SiswaController@index');

    // --- Akses Gabungan Multi-Role (Jurusan, Sarpras, Keuangan, Kepsek) ---
    $router->group(['middleware' => 'role:jurusan,sarpras,keuangan,kepsek'], function () use ($router) {
        $router->get('pengajuan', 'PengajuanController@index');
        $router->get('pengajuan/{id}', 'PengajuanController@show');
        $router->get('pengajuan-detail/{pengajuan_id}', 'DetailPengajuanController@getByPengajuan');
        $router->put('pengajuan-detail/{id}', 'DetailPengajuanController@update');
        $router->get('pengajuan-logs/{pengajuan_id}', 'LogsStatusController@getHistory');
    });

    // --- Khusus Role: JURUSAN ---
    $router->group(['middleware' => 'role:jurusan'], function () use ($router) {
        // Master Barang (Katalog)
        $router->get('master-barang', 'MasterBarangUmumController@index');
        $router->get('master-barang/{id}', 'MasterBarangUmumController@show');

        // Pengajuan & Keranjang
        $router->post('pengajuan', 'PengajuanController@store');
        $router->put('pengajuan-detail-revisi/{detail_id}', 'PengajuanController@revisiDetailBarang');
        $router->get('keranjang', 'KeranjangController@index');
        $router->post('keranjang', 'KeranjangController@store');
        $router->delete('keranjang/{id}', 'KeranjangController@destroy');
    });

    // --- Khusus Role: SARPRAS ---
    $router->group(['middleware' => 'role:sarpras'], function () use ($router) {
        // CRUD Katalog Master Barang
        $router->post('master-barang', 'MasterBarangUmumController@store');
        $router->put('master-barang/{id}', 'MasterBarangUmumController@update');
        $router->delete('master-barang/{id}', 'MasterBarangUmumController@destroy');

        // Manajemen Stok Inventaris
        $router->post('barang', 'BarangController@store');
        $router->post('barang/{id}/stok-masuk', 'BarangController@stokMasuk');
        $router->post('barang/{id}/stok-keluar', 'BarangController@stokKeluar');
        $router->post('stok-masuk/{id}', 'StokMasukController@store');
        $router->post('stok-keluar/{id}', 'StokKeluarController@store');
    });

    // --- Khusus Akses Approval (Sarpras, Keuangan, Kepsek) ---
    $router->group(['middleware' => 'role:sarpras,keuangan,kepsek'], function () use ($router) {
        $router->put('pengajuan-status/{pengajuan_id}', 'LogsStatusController@updateStatus');
    });

    // --- Modul Peminjaman & Pengembalian (Siswa, Jurusan, Sarpras) ---
    $router->group(['middleware' => 'role:siswa,jurusan,sarpras'], function () use ($router) {
        $router->post('peminjaman', 'PeminjamanController@store');
        $router->get('peminjaman/riwayat', 'PeminjamanController@riwayat');
        $router->post('peminjaman/scan', 'PeminjamanController@scanQr');
        $router->post('scan-qr', 'ScanQrController@scan');
        $router->post('pengembalian', 'PengembalianController@store');
        $router->get('pengembalian/riwayat', 'PengembalianController@riwayatKembali');
    });
});
