<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanQr extends Model
{
    protected $table = 'scan_qr';
    protected $fillable = [
        'user_id',
        'barang_id',
        'tanggal_scan',
        'hasil_scan',
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }
}
