<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekonsiliasis', function (Blueprint $table) {
            $table->id();
            $table->string('minggu_ke'); // teks manual, contoh "W-40"
            $table->date('tanggal_mulai');
            $table->date('tanggal_akhir');
            // "Total ORE" tidak diinput, dihitung otomatis dari HGSO + LGSO.
            $table->enum('parameter', ['HGSO', 'LGSO', 'Waste']);

            $table->decimal('bcm_bm', 14, 2)->nullable();
            $table->decimal('ni_bm', 8, 3)->nullable();
            $table->decimal('fe_bm', 8, 3)->nullable();
            $table->decimal('sio2_bm', 8, 3)->nullable();
            $table->decimal('mgo_bm', 8, 3)->nullable();

            $table->decimal('bcm_real', 14, 2)->nullable();
            $table->decimal('ni_real', 8, 3)->nullable();
            $table->decimal('fe_real', 8, 3)->nullable();
            $table->decimal('sio2_real', 8, 3)->nullable();
            $table->decimal('mgo_real', 8, 3)->nullable();

            $table->timestamps();

            $table->unique(['minggu_ke', 'parameter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekonsiliasis');
    }
};
