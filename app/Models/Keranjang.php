<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keranjang extends Model
{
    protected $table = 'keranjang';
    protected $fillable = [
        'user_id',
        'master_barang_id',
        'nama_barang',
        'jumlah',
        'harga_satuan',
        'total_harga',
        'keterangan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function masterBarangUmum()
    {
        return $this->belongsTo(Barang::class, 'master_barang_id');
    }
}
