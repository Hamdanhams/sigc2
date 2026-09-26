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
        Schema::dropIfExists('detail_fsbs');
        Schema::dropIfExists('fsbs');

        Schema::create('fsbs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sampel');
            $table->string('front')->nullable();
            $table->string('titik_produksi')->nullable();
            $table->string('elevasi')->nullable();
            $table->string('huruf_running')->nullable();
            $table->decimal('koordinat_x', 12, 8)->nullable();
            $table->decimal('koordinat_y', 12, 8)->nullable();
            $table->foreignId('personil_id')->nullable()->constrained('personils');
            $table->string('foto_material')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('increment')->nullable();
            $table->string('gridding')->nullable();
            $table->decimal('ni', 8, 4)->nullable();
            $table->decimal('co', 8, 4)->nullable();
            $table->decimal('fe', 8, 4)->nullable();
            $table->decimal('sio2', 8, 4)->nullable();
            $table->decimal('cao', 8, 4)->nullable();
            $table->decimal('mgo', 8, 4)->nullable();
            $table->decimal('cr2o3', 8, 4)->nullable();
            $table->decimal('al2o3', 8, 4)->nullable();
            $table->decimal('ni_bm', 8, 4)->nullable();
            $table->decimal('fe_bm', 8, 4)->nullable();
            $table->enum('status', ['pending', 'selesai'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fsbs');
    }
};
