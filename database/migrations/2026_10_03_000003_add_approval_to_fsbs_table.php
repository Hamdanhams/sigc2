<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fsbs', function (Blueprint $table) {
            $table->foreignId('user_pegawai_id')->nullable()->after('personil_id')->constrained('user_pegawais')->nullOnDelete();
            $table->enum('status_approval', ['menunggu', 'menunggu_wuh', 'disetujui', 'ditolak'])->default('menunggu')->after('fe_bm');
            $table->text('catatan_penolakan')->nullable()->after('status_approval');
            $table->enum('ditolak_oleh_jabatan', ['pengawas', 'work_unit_head'])->nullable()->after('catatan_penolakan');
            $table->index('status_approval');
        });

        // Data FSBS lama dibuat sebelum ada approval -> dianggap sudah disetujui.
        DB::table('fsbs')->update(['status_approval' => 'disetujui']);
    }

    public function down(): void
    {
        Schema::table('fsbs', function (Blueprint $table) {
            $table->dropForeign(['user_pegawai_id']);
            $table->dropIndex(['status_approval']);
            $table->dropColumn(['user_pegawai_id', 'status_approval', 'catatan_penolakan', 'ditolak_oleh_jabatan']);
        });
    }
};
