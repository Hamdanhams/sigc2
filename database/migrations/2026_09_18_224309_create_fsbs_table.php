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
        Schema::create('fsbs', function (Blueprint $table) {
            $table->id();
            $table->string('front')->nullable(); // teks bebas, auto dari parsing tapi bisa diedit
            $table->decimal('koordinat_x', 12, 8)->nullable();
            $table->decimal('koordinat_y', 12, 8)->nullable();
            $table->foreignId('personil_id')->nullable()->constrained('personils');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fsbs');
    }
};
