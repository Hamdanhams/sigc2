<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fsbs', function (Blueprint $table) {
            // Hasil analisa Ni (%), diisi lewat aplikasi (ketuk marker plot) atau admin. Nullable = belum ada hasil.
            $table->decimal('ni', 6, 3)->nullable()->after('fe_bm');
        });
    }

    public function down(): void
    {
        Schema::table('fsbs', function (Blueprint $table) {
            $table->dropColumn('ni');
        });
    }
};
