<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Kosongkan tabel user terlebih dahulu agar tidak duplikat saat di-seed ulang
        //DB::table('users')->truncate();

        // Input data user percontohan untuk semua role sistem kamu dan temanmu
        DB::table('users')->insert([
            [
                'nama_user' => 'Pak Budi (Sarpras)',
                'username'  => 'sarpras',
                'password'  => Hash::make('password123'), // Password untuk login
                'email'     => 'sarpras@example.com',
                'role'      => 'sarpras',
                'created_at'=> date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ],
            [
                'nama_user' => 'Ketua Jurusan RPL',
                'username'  => 'jurusan',
                'password'  => Hash::make('password123'),
                'email'     => 'jurusan@example.com',
                'role'      => 'jurusan',
                'created_at'=> date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ],
            [
                'nama_user' => 'Bu Siti (Keuangan)',
                'username'  => 'keuangan',
                'password'  => Hash::make('password123'),
                'email'     => 'keuangan@example.com',
                'role'      => 'keuangan',
                'created_at'=> date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ],
            [
                'nama_user' => 'Kepala Sekolah SMK',
                'username'  => 'kepsek',
                'password'  => Hash::make('password123'),
                'email'     => 'kepsek@example.com',
                'role'      => 'kepsek',
                'created_at'=> date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ],
            [
                'nama_user' => 'Andi (Siswa RPL)',
                'username'  => 'siswa',
                'password'  => Hash::make('password123'),
                'email'     => 'siswa@example.com',
                'role'      => 'siswa',
                'created_at'=> date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ],
        ]);
    }
}