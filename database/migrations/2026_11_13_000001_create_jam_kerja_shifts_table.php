<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jam_kerja_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift', 1)->unique();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->timestamps();
        });

        // Tiga shift tetap (sama dengan pilihan shift di laporan Produksi); jamnya diisi admin.
        foreach (['1', '2', '3'] as $shift) {
            DB::table('jam_kerja_shifts')->insert([
                'shift' => $shift,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jam_kerja_shifts');
    }
};
