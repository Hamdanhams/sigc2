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
        Schema::table('peta_layers', function (Blueprint $table) {
            $table->decimal('center_lat', 10, 6)->nullable();
            $table->decimal('center_lon', 10, 6)->nullable();
            $table->integer('min_zoom')->nullable();
            $table->integer('max_zoom')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('peta_layers', function (Blueprint $table) {
            $table->dropColumn(['center_lat', 'center_lon', 'min_zoom', 'max_zoom']);
        });
    }
};
