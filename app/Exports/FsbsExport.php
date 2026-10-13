<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Export FSBS (record terpilih di tabel admin) ke file Excel (.xlsx). */
class FsbsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    public function __construct(private Collection $records) {}

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Kode Sampel',
            'Front',
            'Titik Produksi',
            'Elevasi',
            'Koordinat X',
            'Koordinat Y',
            'Ni BM',
            'Fe BM',
        ];
    }

    /** @param mixed $row */
    public function map($row): array
    {
        return [
            $row->kode_sampel,
            $row->front,
            $row->titik_produksi,
            $row->elevasi,
            $row->koordinat_x !== null ? (float) $row->koordinat_x : null,
            $row->koordinat_y !== null ? (float) $row->koordinat_y : null,
            $row->ni_bm !== null ? (float) $row->ni_bm : null,
            $row->fe_bm !== null ? (float) $row->fe_bm : null,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => '0.00',
            'F' => '0.00',
            'G' => NumberFormat::FORMAT_NUMBER_00,
            'H' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
