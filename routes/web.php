<?php

/** @var \Laravel\Lumen\Routing\Router $router */

// =========================================================================
// Rute Bawaan & Status Utama
// =========================================================================
$router->get('/', ['middleware' => 'cors', function () use ($router) {
    return response()->json([
        'status' => 'success',
        'message' => 'Lumen API Sistem Gabungan (Pengajuan & Inventaris) Siap Digunakan!',
        'version' => $router->app->version()
    ]);
}]);

// =========================================================================
// 1. KELOMPOK RUTE PUBLIK (BEBAS TOKEN)
// =========================================================================
$router->group(['prefix' => 'api', 'middleware' => 'cors'], function () use ($router) {
    // Jalur login utama
    $router->post('/login', ['as' => 'login', 'uses' => 'UserController@login']);

    // Melihat daftar inventaris secara publik
    $router->get('/barang', 'BarangController@index');
});

// =========================================================================
// 2. KELOMPOK RUTE TERPROTEKSI (WAJIB LOGIN - JWT & CORS)
// =========================================================================
$router->group(['prefix' => 'api', 'middleware' => ['cors', 'auth']], function () use ($router) {

    // --- Manajemen User (Gunakan tunggal '/user' agar singkron dengan Axios Frontend) ---
    $router->get('/user', 'UserController@index');
    $router->post('/user', 'UserController@store');
    $router->get('/user/{id}', 'UserController@show');
    $router->delete('/user/{id}', 'UserController@destroy');

    $router->get('/profile', 'UserController@profile');
    $router->put('/profile', 'UserController@updateProfile');
    $router->get('/dashboard-siswa', 'SiswaController@index');

    // Pencarian kode barang
    $router->get('/barang/cari/{kode_barang}', 'BarangController@showByKode');

    // --- Akses Gabungan Multi-Role (Jurusan, Sarpras, Keuangan, Kepsek) ---
    $router->group(['middleware' => 'role:jurusan,sarpras,keuangan,kepsek'], function () use ($router) {
        $router->get('/pengajuan', 'PengajuanController@index');
        $router->get('/pengajuan/{id}', 'PengajuanController@show');
        $router->get('/pengajuan-detail/{pengajuan_id}', 'DetailPengajuanController@getByPengajuan');
        $router->put('/pengajuan-detail/{id}', 'DetailPengajuanController@update');
        $router->get('/pengajuan-logs/{pengajuan_id}', 'LogsStatusController@getHistory');
    });

    // --- Khusus Role: JURUSAN ---
    $router->group(['middleware' => 'role:jurusan'], function () use ($router) {
        // Master Barang (Katalog)
        $router->get('/master-barang', 'MasterBarangUmumController@index');
        $router->get('/master-barang/{id}', 'MasterBarangUmumController@show');

        // Pengajuan & Keranjang
        $router->post('/pengajuan', 'PengajuanController@store');
        $router->put('/pengajuan-detail-revisi/{detail_id}', 'PengajuanController@revisiDetailBarang');
        $router->get('/keranjang', 'KeranjangController@index');
        $router->post('/keranjang', 'KeranjangController@store');
        $router->delete('/keranjang/{id}', 'KeranjangController@destroy');

        // Pilar Fitur Jurusan
        $router->get('/jurusan/siswa-meminjam', 'JurusanController@getSiswaMeminjam');
        $router->get('/jurusan/siswa-kembali', 'JurusanController@getSiswaKembali');
        $router->get('/jurusan/siswa-semua', 'JurusanController@getSiswaByJurusan');
        $router->post('/jurusan/siswa', 'JurusanController@storeSiswa');
        $router->delete('/jurusan/siswa/{id}', 'JurusanController@destroySiswa');
        $router->delete('/jurusan/bersihkan-riwayat', 'JurusanController@clearRiwayat');
    });

    // --- Khusus Role: SARPRAS ---
    $router->group(['middleware' => 'role:sarpras'], function () use ($router) {
        // Statistik Admin
        $router->get('/dashboard/statistik', 'SarprasDashboardController@getStatistik');

        // Rute manajemen barang oleh Sarpras
        $router->post('/barang', 'BarangController@store');
        
        // Rute stok masuk & keluar dengan pencatatan riwayat (Log)
        $router->get('/stok-masuk', 'StokMasukController@index');
        $router->post('/barang/{id}/stok-masuk', 'StokMasukController@store');
        $router->delete('/stok-masuk/bersihkan', 'StokMasukController@clear');

        $router->get('/stok-keluar', 'StokKeluarController@index');
        $router->post('/barang/{id}/stok-keluar', 'StokKeluarController@store');
        $router->delete('/stok-keluar/bersihkan', 'StokKeluarController@clear');
    });

    // --- Khusus Akses Approval (Sarpras, Keuangan, Kepsek) ---
    $router->group(['middleware' => 'role:sarpras,keuangan,kepsek'], function () use ($router) {
        $router->put('/pengajuan-status/{pengajuan_id}', 'LogsStatusController@updateStatus');
    });

    // --- Modul Peminjaman & Pengembalian (Siswa, Jurusan, Sarpras) ---
    $router->group(['middleware' => 'role:siswa,jurusan,sarpras'], function () use ($router) {
        $router->post('/peminjaman', 'PeminjamanController@store');
        $router->get('/peminjaman/riwayat', 'PeminjamanController@riwayat');
        $router->post('/peminjaman/scan', 'PeminjamanController@scanQr');

        // RUTE SCAN QR
        $router->post('/scan-qr', 'ScanQrController@scan');

        $router->post('/pengembalian', 'PengembalianController@store');
        $router->get('/pengembalian/riwayat', 'PengembalianController@riwayatKembali');

        // Rute untuk mengakses tabel riwayat_peminjaman dan riwayat_pengembalian
        $router->get('/riwayat-peminjaman', 'RiwayatPeminjamanController@index');
        $router->get('/riwayat-pengembalian', 'RiwayatPengembalianController@index');
    });
});
