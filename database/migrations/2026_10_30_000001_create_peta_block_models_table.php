<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peta_block_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('front_id')->constrained('fronts')->cascadeOnDelete();
            // RL (level peta). Data sebaran FSBS yang diplot = elevasi RL + 2.
            $table->decimal('rl', 8, 2);
            // Pembeda bila 1 front + RL dibagi beberapa peta (mis. Utara / Tengah / Selatan). TIDAK unik.
            $table->string('nama_bagian')->nullable();
            $table->string('file_geopdf');
            $table->string('file_mbtiles')->nullable();
            $table->enum('status', ['pending', 'processing', 'selesai', 'gagal'])->default('pending');
            $table->text('pesan_error')->nullable();
            $table->decimal('center_lat', 10, 6)->nullable();
            $table->decimal('center_lon', 10, 6)->nullable();
            $table->integer('min_zoom')->nullable();
            $table->integer('max_zoom')->nullable();
            $table->timestamps();

            $table->index(['front_id', 'rl']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peta_block_models');
    }
};
