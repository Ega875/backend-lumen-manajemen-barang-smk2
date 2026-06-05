<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

    // Tambahkan 2 fungsi wajib ini di bagian bawah:
    
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'nama_user',
        'username', // Ditambahkan agar tidak kosong saat register
        'email',
        'password', // Ditambahkan agar bisa disimpan dan diverifikasi saat login
        'role',
        'status',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'password',
    ];

    // --- JWT Requirement Methods ---
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role // Memasukkan role ke dalam token agar frontend tahu ini siswa/sarpras/dll
        ];
    }

    public function peminjaman()
    {
        return $this->hasMany(Peminjaman::class);
    }

    public function pengajuan()
    {
        return $this->hasMany(Pengajuan::class);
    }

    public function scanQr()
    {
        return $this->hasMany(ScanQr::class);
    }

    public function logsStatus()
    {
        return $this->hasMany(LogsStatus::class);
    }
}
