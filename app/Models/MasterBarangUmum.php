<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterBarangUmum extends Model
{
    protected $table = 'master_barang_umum';
    protected $fillable = [
        'nama_barang',
        'spesifikasi_umum',
        'kategori',
        'harga_satuan',
    ];
}
