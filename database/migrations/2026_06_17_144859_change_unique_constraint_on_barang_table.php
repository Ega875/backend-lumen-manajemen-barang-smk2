<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->dropUnique('barang_kode_barang_unique');
            $table->unique(['kode_barang', 'jurusan']);
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->dropUnique('barang_kode_barang_jurusan_unique');
            $table->unique('kode_barang');
        });
    }
};
