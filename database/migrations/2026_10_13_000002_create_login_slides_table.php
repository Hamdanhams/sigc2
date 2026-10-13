<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_slides', function (Blueprint $table) {
            $table->id();
            $table->string('gambar'); // URL Cloudinary
            $table->string('judul')->nullable();
            $table->string('keterangan', 500)->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['aktif', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_slides');
    }
};
