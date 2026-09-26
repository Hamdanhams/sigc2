<?php

namespace App\Exports;

use App\Models\Produksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProduksiExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $produksiIds;

    public function __construct(array $produksiIds)
    {
        $this->produksiIds = $produksiIds;
    }

    public function collection(): \Illuminate\Support\Collection
    {
        $produksiList = Produksi::whereIn('id', $this->produksiIds)
            ->with('details', 'front')
            ->orderBy('tanggal')
            ->get();

        $rows = collect();

        foreach ($produksiList as $produksi) {
            foreach ($produksi->details as $detail) {
                $elevasiDari = $this->formatElevasi($detail->elevasi_atas);
                $elevasiKe = $detail->elevasi_atas !== null
                    ? $this->formatElevasi($detail->elevasi_atas - 2)
                    : '-';

                $kodeProduksi = ($produksi->front->inisial ?? '')
                    . ($detail->titik_produksi ?? '')
                    . $elevasiDari
                    . ($detail->running_number ?? '');

                $rows->push([
                    'tanggal' => $produksi->tanggal,
                    'kode_produksi' => $kodeProduksi,
                    'elevasi_dari' => $elevasiDari,
                    'elevasi_ke' => $elevasiKe,
                    'ni_bm' => $detail->ni_bm,
                    'fe_bm' => $detail->fe_bm,
                    'tujuan_dumping' => $detail->tujuan_dumping,
                    'ritase' => $detail->ritase,
                    'gridding' => $detail->gridding,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Kode Produksi',
            'Elevasi Dari',
            'Elevasi Ke',
            'Ni BM',
            'Fe BM',
            'Tujuan Dumping',
            'Ritase',
            'Gridding',
        ];
    }

    /**
     * @param array $row
     */
    public function map($row): array
    {
        return [
            \Carbon\Carbon::parse($row['tanggal'])->format('d/m/Y'),
            $row['kode_produksi'],
            $row['elevasi_dari'],
            $row['elevasi_ke'],
            $row['ni_bm'] !== null ? round($row['ni_bm'], 2) : null,
            $row['fe_bm'] !== null ? round($row['fe_bm'], 2) : null,
            $row['tujuan_dumping'],
            $row['ritase'] !== null ? round($row['ritase'], 0) : null,
            $row['gridding'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    protected function formatElevasi($value): string
    {
        if ($value === null) return '-';

        $value = (float) $value;

        if ($value < 0) {
            return 'M' . str_pad((string) abs((int) $value), 2, '0', STR_PAD_LEFT);
        }

        return str_pad((string) (int) $value, 3, '0', STR_PAD_LEFT);
    }
}
