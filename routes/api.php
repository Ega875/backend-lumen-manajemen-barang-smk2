<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarangController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Grouping route untuk resource inventaris barang
Route::prefix('barang')->group(function () {
    Route::get('/', [BarangController::class, 'index']);             // GET /api/barang
    Route::post('/', [BarangController::class, 'store']);            // POST /api/barang
    Route::post('{id}/stok-masuk', [BarangController::class, 'stokMasuk']);   // POST /api/barang/{id}/stok-masuk
    Route::post('{id}/stok-keluar', [BarangController::class, 'stokKeluar']); // POST /api/barang/{id}/stok-keluar
});
