<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPengembalian extends Model
{
    protected $table = 'riwayat_pengembalian';
    protected $fillable = [
        'pengembalian_id',
        'aktivitas',
        'tanggal_aktivitas',
    ];

    public function pengembalian()
    {
        return $this->belongsTo(Pengembalian::class, 'pengembalian_id');
    }
}
