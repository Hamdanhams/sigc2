<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kategori baru: Pengawas Senior (approval lapis 1 cuti).
        DB::statement("ALTER TABLE user_pegawais MODIFY jabatan ENUM('pengawas','pengawas_senior','work_unit_head') NOT NULL DEFAULT 'pengawas'");

        // Saldo cuti Personil (hari). Boleh minus (batas diatur di CutiService).
        Schema::table('personils', function (Blueprint $table) {
            $table->integer('saldo_cuti')->default(0)->after('inisial');
        });

        // Hari libur nasional / cuti bersama (tidak dihitung sebagai hari cuti).
        Schema::create('hari_liburs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personil_id')->constrained('personils')->cascadeOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedSmallInteger('jumlah_hari'); // hari kerja saja (tanpa Sabtu/Minggu/libur)
            $table->text('alasan');
            $table->enum('status', ['menunggu', 'menunggu_wuh', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('catatan_penolakan')->nullable();
            $table->enum('ditolak_oleh_jabatan', ['pengawas_senior', 'work_unit_head'])->nullable();
            // Jejak approval (dipakai juga untuk ekspor PDF).
            $table->foreignId('disetujui_senior_oleh')->nullable()->constrained('user_pegawais')->nullOnDelete();
            $table->timestamp('disetujui_senior_at')->nullable();
            $table->foreignId('disetujui_wuh_oleh')->nullable()->constrained('user_pegawais')->nullOnDelete();
            $table->timestamp('disetujui_wuh_at')->nullable();
            $table->timestamps();

            $table->index(['personil_id', 'status']);
            $table->index('status');
        });

        // Riwayat perubahan saldo (siapa, kapan, kenapa).
        Schema::create('saldo_cuti_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personil_id')->constrained('personils')->cascadeOnDelete();
            $table->integer('perubahan'); // + menambah, - mengurangi
            $table->integer('saldo_sebelum');
            $table->integer('saldo_sesudah');
            $table->enum('jenis', ['manual', 'cuti_disetujui', 'cuti_dikembalikan']);
            $table->foreignId('cuti_id')->nullable()->constrained('cutis')->nullOnDelete();
            $table->string('catatan')->nullable();
            $table->string('oleh')->nullable(); // nama admin / pejabat yang memicu
            $table->timestamps();

            $table->index('personil_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_cuti_logs');
        Schema::dropIfExists('cutis');
        Schema::dropIfExists('hari_liburs');
        Schema::table('personils', function (Blueprint $table) {
            $table->dropColumn('saldo_cuti');
        });
        DB::statement("UPDATE user_pegawais SET jabatan = 'pengawas' WHERE jabatan = 'pengawas_senior'");
        DB::statement("ALTER TABLE user_pegawais MODIFY jabatan ENUM('pengawas','work_unit_head') NOT NULL DEFAULT 'pengawas'");
    }
};
