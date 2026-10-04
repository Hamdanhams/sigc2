<?php

namespace App\Services;

use App\Models\Rekonsiliasi;
use Illuminate\Support\Collection;

/**
 * Menyusun data Rekonsiliasi per periode (minggu) untuk dikonsumsi grafik.
 *
 * "Total ORE" tidak disimpan; dihitung dari HGSO + LGSO:
 *  - BCM        : dijumlahkan.
 *  - Ni/Fe/SiO2/MgO (kadar): rata-rata tertimbang BCM, dengan BCM dari sisi
 *    yang sama (BM dengan BCM BM, Real dengan BCM Real). Rata-rata biasa salah
 *    secara teknis karena volume HGSO dan LGSO berbeda.
 */
class RekonsiliasiService
{
    public const JENIS = ['bcm', 'ni', 'fe', 'sio2', 'mgo'];

    public const PARAMETER = ['HGSO', 'LGSO', 'Total ORE', 'Waste'];

    /**
     * @return array<int, array{minggu_ke:string, tanggal_mulai:string, tanggal_akhir:string, data:array}>
     */
    public function semuaPeriode(): array
    {
        return Rekonsiliasi::orderByDesc('tanggal_mulai')
            ->get()
            ->groupBy('minggu_ke')
            ->map(fn(Collection $rows, string $minggu) => $this->susunPeriode($minggu, $rows))
            ->values()
            ->all();
    }

    private function susunPeriode(string $minggu, Collection $rows): array
    {
        $byParam = $rows->keyBy('parameter');
        $first = $rows->first();

        $data = [];
        foreach (['HGSO', 'LGSO', 'Waste'] as $param) {
            $data[$param] = isset($byParam[$param]) ? $this->nilai($byParam[$param]) : null;
        }
        $data['Total ORE'] = $this->totalOre($byParam->get('HGSO'), $byParam->get('LGSO'));

        return [
            'minggu_ke' => $minggu,
            'tanggal_mulai' => $first->tanggal_mulai->toDateString(),
            'tanggal_akhir' => $first->tanggal_akhir->toDateString(),
            'data' => $data,
        ];
    }

    /** Nilai BM & Real per jenis untuk 1 baris. */
    private function nilai(Rekonsiliasi $row): array
    {
        $out = [];
        foreach (self::JENIS as $jenis) {
            $out[$jenis] = [
                'bm' => $this->num($row->{$jenis . '_bm'}),
                'real' => $this->num($row->{$jenis . '_real'}),
            ];
        }
        return $out;
    }

    private function totalOre(?Rekonsiliasi $hgso, ?Rekonsiliasi $lgso): ?array
    {
        $rows = array_values(array_filter([$hgso, $lgso]));
        if (empty($rows)) {
            return null;
        }

        $out = [];
        foreach (self::JENIS as $jenis) {
            foreach (['bm', 'real'] as $sisi) {
                $out[$jenis][$sisi] = $jenis === 'bcm'
                    ? $this->jumlah($rows, "bcm_$sisi")
                    : $this->rataTertimbang($rows, "{$jenis}_$sisi", "bcm_$sisi");
            }
        }
        return $out;
    }

    private function jumlah(array $rows, string $kolom): ?float
    {
        $nilai = array_filter(array_map(fn($r) => $this->num($r->$kolom), $rows), fn($v) => $v !== null);
        return empty($nilai) ? null : round(array_sum($nilai), 2);
    }

    private function rataTertimbang(array $rows, string $kolomKadar, string $kolomBcm): ?float
    {
        $atas = 0.0;
        $bawah = 0.0;
        foreach ($rows as $r) {
            $kadar = $this->num($r->$kolomKadar);
            $bcm = $this->num($r->$kolomBcm);
            if ($kadar === null || $bcm === null) {
                continue;
            }
            $atas += $kadar * $bcm;
            $bawah += $bcm;
        }
        return $bawah > 0 ? round($atas / $bawah, 3) : null;
    }

    private function num($v): ?float
    {
        return $v === null ? null : (float) $v;
    }
}
