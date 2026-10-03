<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_pegawais', function (Blueprint $table) {
            $table->enum('jabatan', ['pengawas', 'work_unit_head'])->default('pengawas')->after('npp');
        });
    }

    public function down(): void
    {
        Schema::table('user_pegawais', function (Blueprint $table) {
            $table->dropColumn('jabatan');
        });
    }
};
