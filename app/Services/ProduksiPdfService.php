<?php

namespace App\Services;

use App\Models\Produksi;
use App\Models\UserPegawai;
use Barryvdh\DomPDF\Facade\Pdf;

class ProduksiPdfService
{
    public function generate($produksiList, int $workUnitHeadId)
    {
        $workUnitHead = UserPegawai::find($workUnitHeadId);

        $laporanData = [];

        foreach ($produksiList as $produksi) {
            $details = [];
            $totalRitase = 0;

            foreach ($produksi->details as $detail) {
                $elevasiFormatted = $this->formatElevasi($detail->elevasi_atas);
                $keFormatted = $detail->elevasi_atas !== null
                    ? $this->formatElevasi($detail->elevasi_atas - 2)
                    : '-';

                $kodeProduksiTambang = ($produksi->front->inisial ?? '')
                    . ($detail->titik_produksi ?? '')
                    . $elevasiFormatted
                    . ($detail->running_number ?? '');

                $details[] = [
                    'kode_produksi_tambang' => $kodeProduksiTambang,
                    'dari' => $elevasiFormatted,
                    'ke' => $keFormatted,
                    'tujuan_dumping' => $detail->tujuan_dumping,
                    'ritase' => $detail->ritase,
                    'ni_bm' => $detail->ni_bm,
                    'fe_bm' => $detail->fe_bm,
                    'gridding' => $detail->gridding,
                ];

                $totalRitase += (float) ($detail->ritase ?? 0);
            }

            $laporanData[] = [
                'produksi' => $produksi,
                'details' => $details,
                'totalRitase' => $totalRitase,
                'picTtd' => $this->getTtdBase64($produksi->pic1?->ttd),
                'pengawasTtd' => $this->getTtdBase64($produksi->userPegawai?->ttd),
            ];
        }

        $headTtd = $this->getTtdBase64($workUnitHead?->ttd);

        $pdf = Pdf::loadView('pdf.laporan-produksi', [
            'laporanData' => $laporanData,
            'workUnitHead' => $workUnitHead,
            'headTtd' => $headTtd,
            'tanggalCetak' => now()->translatedFormat('d F Y'),
        ])->setPaper('a4', 'landscape');

        return $pdf;
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

    protected function getTtdBase64(?string $path): ?string
    {
        if (!$path) return null;

        $fullPath = storage_path('app/private/' . $path);

        if (!file_exists($fullPath)) return null;

        $data = base64_encode(file_get_contents($fullPath));
        $mime = mime_content_type($fullPath);

        return "data:$mime;base64,$data";
    }
}
