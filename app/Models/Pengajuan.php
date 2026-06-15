<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengajuan extends Model
{
    protected $table = 'pengajuan';
    protected $fillable = [
        'user_id',
        'barang_id',
        'status',
        'catatan_revisi',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function logsStatus()
    {
        return $this->hasMany(LogsStatus::class, 'pengajuan_id');
    }

    public function detail_pengajuan()
    {
        return $this->hasMany(DetailPengajuan::class, 'pengajuan_id');
    }
}
