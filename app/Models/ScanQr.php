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

    // PERBAIKAN: Relasi disesuaikan dengan foreignId yang ada di migrasi kamu
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
