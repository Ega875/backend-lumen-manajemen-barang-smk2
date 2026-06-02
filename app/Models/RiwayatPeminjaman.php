<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPeminjaman extends Model
{
    protected $table = 'riwayat_peminjaman';
    protected $fillable = [
        'peminjaman_id',
        'aktivitas',
        'tanggal_aktivitas',
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }
}
