<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sebaran_fsbs', function (Blueprint $table) {
            $table->index(['inisial_front', 'titik_produksi'], 'sebaran_fsbs_front_titik_idx');
            $table->index('titik_produksi', 'sebaran_fsbs_titik_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sebaran_fsbs', function (Blueprint $table) {
            $table->dropIndex('sebaran_fsbs_front_titik_idx');
            $table->dropIndex('sebaran_fsbs_titik_idx');
        });
    }
};
