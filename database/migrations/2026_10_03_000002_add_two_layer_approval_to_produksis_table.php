<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE produksis MODIFY status_approval ENUM('menunggu','menunggu_wuh','disetujui','ditolak') NOT NULL DEFAULT 'menunggu'");

        Schema::table('produksis', function (Blueprint $table) {
            $table->enum('ditolak_oleh_jabatan', ['pengawas', 'work_unit_head'])->nullable()->after('catatan_penolakan');
        });
    }

    public function down(): void
    {
        Schema::table('produksis', function (Blueprint $table) {
            $table->dropColumn('ditolak_oleh_jabatan');
        });

        DB::statement("UPDATE produksis SET status_approval = 'menunggu' WHERE status_approval = 'menunggu_wuh'");
        DB::statement("ALTER TABLE produksis MODIFY status_approval ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu'");
    }
};
