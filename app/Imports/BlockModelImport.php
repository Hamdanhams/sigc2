<?php

namespace App\Imports;

use App\Models\BlockModel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class BlockModelImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, ShouldQueue, SkipsEmptyRows
{
    public function model(array $row): \App\Models\BlockModel
    {
        return new BlockModel([
            'inisial_front'   => $row['inisial_front'] ?? $row['front'] ?? null,
            'titik_produksi'  => $row['titik_produksi'] ?? null,
            'elevasi_genap'   => $row['elevasi_genap'] ?? null,
            'elevasi_ganjil'  => $row['elevasi_ganjil'] ?? null,
            'ni_persen'       => $row['ni_persen'] ?? $row['ni'] ?? null,
            'fe_persen'       => $row['fe_persen'] ?? $row['fe'] ?? null,
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
