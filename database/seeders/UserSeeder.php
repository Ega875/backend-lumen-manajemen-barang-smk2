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
        // 1. PERBAIKAN UTAMA: Matikan proteksi foreign key constraint agar bisa di-truncate
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        // 2. Kosongkan tabel users (Sekarang dijamin lancar tanpa error)
        DB::table('users')->truncate();

        // 3. Input ulang data user percontohan
        DB::table('users')->insert([
            [
                'nama_user' => 'Pak Budi (Sarpras)',
                'username'  => 'sarpras',
                'password'  => Hash::make('password123'),
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
                'jurusan'   => 'RPL',
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
                'jurusan'   => 'RPL',
                'created_at'=> date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ],
        ]);

        // 4. PERBAIKAN UTAMA: Aktifkan kembali proteksi foreign key demi keamanan database
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }
}
