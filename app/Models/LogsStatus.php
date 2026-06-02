<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogsStatus extends Model
{
    protected $table = 'logs_status';
    protected $fillable = [
        'pengajuan_id',
        'user_id',
        'status_sebelumnya',
        'status_sesudah',
        'keterangan',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
