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
        Schema::create('peta_sebaran_layers', function (Blueprint $table) {
            $table->id();
            $table->string('nama_peta');
            $table->foreignId('front_id')->nullable()->constrained('fronts');
            $table->string('file_geopdf');
            $table->string('file_mbtiles')->nullable();
            $table->enum('status', ['pending', 'processing', 'selesai', 'gagal'])->default('pending');
            $table->text('pesan_error')->nullable();
            $table->decimal('center_lat', 10, 6)->nullable();
            $table->decimal('center_lon', 10, 6)->nullable();
            $table->integer('min_zoom')->nullable();
            $table->integer('max_zoom')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peta_sebaran_layers');
    }
};
