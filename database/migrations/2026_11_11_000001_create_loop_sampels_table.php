<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loop_sampels', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('kode_sample')->nullable();
            $table->string('incr', 20)->nullable();
            $table->string('inisial_front', 10)->nullable();
            $table->string('zona')->nullable();
            $table->string('warna')->nullable();
            $table->string('ukuran')->nullable();
            $table->text('komposisi')->nullable();
            $table->text('keterangan')->nullable();

            foreach (['ni', 'co', 'fe', 'sio2', 'cao', 'mgo', 'cr2o3', 'al2o3', 'cr', 'fe_ni', 's_m', 'bc'] as $kolom) {
                $table->decimal($kolom, 9, 3)->nullable();
            }

            $table->string('foto')->nullable();           // path di disk public, folder loop/
            $table->longText('embedding')->nullable();    // JSON: sidik jari visual foto
            $table->string('model_versi', 40)->nullable();
            $table->string('status', 10)->default('pending'); // pending | siap | gagal
            $table->text('pesan_error')->nullable();
            $table->timestamps();

            $table->index('inisial_front');
            $table->index('zona');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loop_sampels');
    }
};
