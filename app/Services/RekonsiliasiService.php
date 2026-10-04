<?php

namespace App\Services;

use App\Models\Rekonsiliasi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /** Parameter yang diinput manual -> kunci grup di form. Total ORE dihitung otomatis. */
    public const INPUT_PARAMETER = ['HGSO' => 'hgso', 'LGSO' => 'lgso', 'Waste' => 'waste'];

    private const KOLOM_NILAI = [
        'bcm_bm', 'ni_bm', 'fe_bm', 'sio2_bm', 'mgo_bm',
        'bcm_real', 'ni_real', 'fe_real', 'sio2_real', 'mgo_real',
    ];

    /** Apakah blok 1 parameter berisi minimal satu angka. */
    public function blokTerisi(?array $blok): bool
    {
        foreach (self::KOLOM_NILAI as $kolom) {
            if (isset($blok[$kolom]) && $blok[$kolom] !== '') {
                return true;
            }
        }
        return false;
    }

    /** Minimal satu dari HGSO/LGSO/Waste terisi. */
    public function adaBlokTerisi(array $data): bool
    {
        foreach (self::INPUT_PARAMETER as $kunci) {
            if ($this->blokTerisi($data[$kunci] ?? null)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Simpan 1 minggu sekaligus (form: header + blok HGSO/LGSO/Waste).
     * Blok kosong tidak disimpan (dan dihapus kalau sebelumnya ada).
     * $mingguLama dipakai saat edit (nama minggu boleh diganti).
     */
    public function simpanMinggu(array $data, ?string $mingguLama = null): Rekonsiliasi
    {
        return DB::transaction(function () use ($data, $mingguLama) {
            $header = [
                'minggu_ke' => $data['minggu_ke'],
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_akhir' => $data['tanggal_akhir'],
            ];

            if ($mingguLama !== null) {
                Rekonsiliasi::where('minggu_ke', $mingguLama)->update($header);
            }

            $hasil = null;
            foreach (self::INPUT_PARAMETER as $parameter => $kunci) {
                $blok = $data[$kunci] ?? null;
                $kunciBaris = ['minggu_ke' => $header['minggu_ke'], 'parameter' => $parameter];

                if (!$this->blokTerisi($blok)) {
                    Rekonsiliasi::where($kunciBaris)->delete();
                    continue;
                }

                $nilai = [];
                foreach (self::KOLOM_NILAI as $kolom) {
                    $v = $blok[$kolom] ?? null;
                    $nilai[$kolom] = ($v === '' || $v === null) ? null : $v;
                }

                $hasil = Rekonsiliasi::updateOrCreate($kunciBaris, $header + $nilai);
            }

            return $hasil;
        });
    }

    /** Muat 1 minggu ke bentuk data form. */
    public function muatMinggu(string $minggu): array
    {
        $rows = Rekonsiliasi::where('minggu_ke', $minggu)->get()->keyBy('parameter');
        $first = $rows->first();

        $data = [
            'minggu_ke' => $minggu,
            'tanggal_mulai' => $first?->tanggal_mulai?->toDateString(),
            'tanggal_akhir' => $first?->tanggal_akhir?->toDateString(),
        ];

        foreach (self::INPUT_PARAMETER as $parameter => $kunci) {
            $data[$kunci] = isset($rows[$parameter])
                ? $rows[$parameter]->only(self::KOLOM_NILAI)
                : [];
        }

        return $data;
    }

    public function hapusMinggu(string $minggu): void
    {
        Rekonsiliasi::where('minggu_ke', $minggu)->delete();
    }

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
