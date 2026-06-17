<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeminjamanAlat extends Model
{
    protected $table = 'peminjaman_alat';

    protected $fillable = [
        'siswa_id',
        'nama_siswa',
        'nama_alat',
        'kode_alat',
        'jurusan_alat',
        'status',
        'foto_kondisi',
        'keterangan_kondisi',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
}
