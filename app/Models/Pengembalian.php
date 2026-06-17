<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengembalian extends Model
{
    protected $table = 'pengembalian';

    protected $fillable = [
        'peminjaman_id',
        'tanggal_kembali',
        'image_bukti_kembali',
        'deskripsi_kembali',
    ];

    // Relasi ke data peminjaman asalnya
    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    public function riwayatPengembalian()
    {
        return $this->hasMany(RiwayatPengembalian::class, 'pengembalian_id');
    }
}
