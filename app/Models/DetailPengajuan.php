<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPengajuan extends Model
{
    protected $table = 'detail_pengajuan';
    protected $fillable = [
        'pengajuan_id',
        'master_barang_id',
        'nama_barang',
        'jumlah',
        'harga_satuan',
        'total_harga',
        'keterangan',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id');
    }

    public function masterBarangUmum()
    {
        return $this->belongsTo(Barang::class, 'master_barang_id');
    }
}
