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
        Schema::table('produksis', function (Blueprint $table) {
            $table->enum('status_approval', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu')->after('rencana_produksi_besok');
            $table->text('catatan_penolakan')->nullable()->after('status_approval');
        });
    }

    public function down(): void
    {
        Schema::table('produksis', function (Blueprint $table) {
            $table->dropColumn(['status_approval', 'catatan_penolakan']);
        });
    }
};
