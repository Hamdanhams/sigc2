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
        Schema::create('sebaran_fsbs', function (Blueprint $table) {
            $table->id();
            $table->string('inisial_front'); // teks bebas, sama seperti Block Model
            $table->string('titik_produksi');
            $table->string('elevasi');
            $table->decimal('koordinat_x', 16, 8);
            $table->decimal('koordinat_y', 16, 8);
            $table->decimal('ni', 8, 4)->nullable();
            $table->decimal('fe', 8, 4)->nullable();
            $table->decimal('si_mg_ratio', 8, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sebaran_fsbs');
    }
};
