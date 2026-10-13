<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** tabel => [nama_index => kolom[]] */
    private function indexes(): array
    {
        return [
            // Lookup BM (API + form) dan pencarian admin: sebelumnya tanpa index sama sekali
            'block_models' => [
                'block_models_front_titik_idx' => ['inisial_front', 'titik_produksi'],
            ],
            'fsbs' => [
                'fsbs_created_at_idx' => ['created_at'],
                'fsbs_front_created_idx' => ['front', 'created_at'],
                'fsbs_kode_sampel_idx' => ['kode_sampel'],
            ],
            'produksis' => [
                'produksis_tanggal_idx' => ['tanggal'],
                'produksis_status_approval_idx' => ['status_approval'],
            ],
            'permintaans' => [
                'permintaans_status_idx' => ['status'],
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->indexes() as $table => $list) {
            Schema::table($table, function (Blueprint $t) use ($table, $list) {
                foreach ($list as $name => $cols) {
                    if (! Schema::hasIndex($table, $name)) {
                        $t->index($cols, $name);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes() as $table => $list) {
            Schema::table($table, function (Blueprint $t) use ($table, $list) {
                foreach (array_keys($list) as $name) {
                    if (Schema::hasIndex($table, $name)) {
                        $t->dropIndex($name);
                    }
                }
            });
        }
    }
};
