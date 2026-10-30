<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_dokumens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('front_id')->constrained('fronts')->cascadeOnDelete();
            $table->string('judul');
            $table->string('file_pdf');
            $table->unsignedBigInteger('ukuran')->nullable();
            $table->timestamps();

            $table->index('front_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_dokumens');
    }
};
