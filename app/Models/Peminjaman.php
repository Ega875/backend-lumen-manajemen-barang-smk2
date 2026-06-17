<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Peminjaman extends Model
{
    protected $table = 'peminjaman';

    protected $fillable = [
        'user_id',
        'barang_id',
        'tanggal_pinjam',
        'jumlah_pinjam',
        'status',
    ];

    protected $appends = ['tanggal_kembali'];

    public function getTanggalKembaliAttribute()
    {
        return $this->pengembalian ? $this->pengembalian->tanggal_kembali : null;
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pengembalian()
    {
        return $this->hasOne(Pengembalian::class, 'peminjaman_id');
    }

    public function riwayatPeminjaman()
    {
        return $this->hasMany(RiwayatPeminjaman::class, 'peminjaman_id');
    }
}
