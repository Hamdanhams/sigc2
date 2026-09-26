<?php

namespace App\Imports;

use App\Models\SebaranFsbs;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class SebaranFsbsImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, ShouldQueue, SkipsEmptyRows
{
    public function model(array $row): SebaranFsbs
    {
        return new SebaranFsbs([
            'inisial_front'  => $row['inisial_front'] ?? $row['front'] ?? null,
            'titik_produksi' => $row['titik_produksi'] ?? null,
            'elevasi'        => $row['elevasi'] ?? null,
            'koordinat_x'    => $row['x'] ?? $row['koordinat_x'] ?? null,
            'koordinat_y'    => $row['y'] ?? $row['koordinat_y'] ?? null,
            'ni'             => $row['ni'] ?? null,
            'fe'             => $row['fe'] ?? null,
            'si_mg_ratio'    => $row['s_m'] ?? $row['sm'] ?? $row['si_mg_ratio'] ?? null,
        ]);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 500;
    }
}
