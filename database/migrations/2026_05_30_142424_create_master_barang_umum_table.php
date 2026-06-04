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
        Schema::create('master_barang_umum', function (Blueprint $table) {
            $table->id();
            $table->string('nama_barang');
            $table->text('spesifikasi_umum')->nullable();
            $table->string('kategori');
            $table->decimal('harga_satuan', 15, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_barang_umum');
    }
};
