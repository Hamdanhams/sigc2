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
        Schema::table('fsbs', function (Blueprint $table) {
            $table->decimal('koordinat_x', 16, 8)->nullable()->change();
            $table->decimal('koordinat_y', 16, 8)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('fsbs', function (Blueprint $table) {
            $table->decimal('koordinat_x', 12, 8)->nullable()->change();
            $table->decimal('koordinat_y', 12, 8)->nullable()->change();
        });
    }
};
