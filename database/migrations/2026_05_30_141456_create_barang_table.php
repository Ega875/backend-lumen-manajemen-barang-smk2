<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('barangs', function (Blueprint $table) {
        $table->id();
        $table->string('kode_barang')->unique(); // Contoh: BRG-TKJ-2026-001
        $table->string('nama_barang');
        $table->string('kategori');
        $table->integer('stok')->default(0);
        $table->timestamps();
    });
    
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
