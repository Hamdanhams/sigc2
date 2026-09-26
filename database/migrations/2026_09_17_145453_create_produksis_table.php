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
        Schema::create('produksis', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->enum('shift', ['1', '2', '3']);
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->foreignId('front_id')->constrained('fronts');
            $table->string('fleet')->nullable();
            $table->foreignId('pic_1_id')->nullable()->constrained('personils');
            $table->foreignId('pic_2_id')->nullable()->constrained('personils');
            $table->foreignId('user_pegawai_id')->nullable()->constrained('user_pegawais');
            $table->json('dokumentasi_produksi')->nullable(); // array URL Cloudinary
            $table->json('dokumentasi_kendala')->nullable(); // array URL Cloudinary
            $table->text('keterangan')->nullable();
            $table->text('rencana_produksi_besok')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produksis');
    }
};
