<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_meetings', function (Blueprint $table) {
            $table->id();
            // Dibuat oleh aplikasi untuk mencegah data ganda saat kiriman ulang (antrean offline).
            $table->uuid('client_uuid')->nullable()->unique();
            $table->foreignId('user_pegawai_id')->constrained('user_pegawais')->cascadeOnDelete();
            $table->dateTime('waktu'); // saat foto diambil (disimpan UTC)
            $table->string('lokasi');
            $table->json('anggota'); // [{id, nama}, ...]
            $table->text('pembahasan');
            $table->string('foto'); // URL Cloudinary (JPEG)
            $table->timestamps();

            $table->index('waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_meetings');
    }
};
