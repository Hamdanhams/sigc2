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
        Schema::create('block_models', function (Blueprint $table) {
            $table->id();
            $table->string('inisial_front'); // teks bebas, tidak wajib cocok tabel fronts
            $table->string('titik_produksi');
            $table->string('elevasi_genap')->nullable();
            $table->string('elevasi_ganjil')->nullable();
            $table->decimal('ni_persen', 8, 4)->nullable();
            $table->decimal('fe_persen', 8, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('block_models');
    }
};
