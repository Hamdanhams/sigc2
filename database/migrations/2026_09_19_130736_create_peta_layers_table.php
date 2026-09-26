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
        Schema::create('peta_layers', function (Blueprint $table) {
            $table->id();
            $table->string('nama_peta'); // misal "Plan W38", "Section RL22"
            $table->foreignId('front_id')->nullable()->constrained('fronts');
            $table->string('file_geopdf'); // path file GeoPDF asli
            $table->string('file_mbtiles')->nullable(); // path hasil konversi
            $table->enum('status', ['pending', 'processing', 'selesai', 'gagal'])->default('pending');
            $table->text('pesan_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peta_layers');
    }
};
