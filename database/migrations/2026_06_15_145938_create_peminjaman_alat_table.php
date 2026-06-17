<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('peminjaman_alat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('users')->onDelete('cascade');
            $table->string('nama_siswa');
            $table->string('nama_alat');
            $table->string('kode_alat');
            $table->string('jurusan_alat'); // Pelacakan internal jurusan
            $table->enum('status', ['dipinjam', 'dikembalikan'])->default('dipinjam');
            $table->string('foto_kondisi')->nullable(); // URL / Path foto dari siswa
            $table->text('keterangan_kondisi')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('peminjaman_alat');
    }
};
