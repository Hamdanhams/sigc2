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
        Schema::create('detail_produksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produksi_id')->constrained('produksis')->cascadeOnDelete();
            $table->string('titik_produksi');
            $table->decimal('elevasi_atas', 10, 2)->nullable();
            $table->string('running_number')->nullable(); // diisi via Service, belum dibangun
            $table->string('tujuan_dumping')->nullable();
            $table->decimal('ni_bm', 8, 4)->nullable(); // hasil lookup, belum dibangun
            $table->decimal('fe_bm', 8, 4)->nullable();
            $table->decimal('ritase', 10, 2)->nullable();
            $table->string('gridding')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_produksis');
    }
};
