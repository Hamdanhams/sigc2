<?php

namespace App\Services;

/**
 * Cap "dokumen otomatis" pada PDF Produksi & FSBS. Hanya boleh dipasang pada laporan yang sudah
 * lolos kedua lapis persetujuan (status `disetujui`); pemeriksaan itu dilakukan sebelum PDF dibuat.
 */
class CapDokumenService
{
    /** Teks cap; tanggal = saat PDF diunduh, dalam WITA. */
    public static function teks(): string
    {
        $waktu = now('Asia/Makassar')->locale('id')->translatedFormat('d F Y, H:i') . ' WITA';

        return 'DOKUMEN OTOMATIS — Diterbitkan dan diverifikasi melalui sistem LACAK'
            . ' · disetujui Pengawas dan Work Unit Head'
            . ' · dicetak ' . $waktu;
    }

    /**
     * Ringkas jumlah laporan yang belum disetujui, mis. "2 Menunggu Pengawas, 1 Ditolak".
     * Kosong bila semua sudah disetujui.
     *
     * @param  iterable<object>  $records  objek dengan properti status_approval
     */
    public static function ringkasBelumDisetujui(iterable $records): string
    {
        $label = [
            'menunggu' => 'Menunggu Pengawas',
            'menunggu_wuh' => 'Menunggu WUH',
            'ditolak' => 'Ditolak',
        ];
        $hitung = [];

        foreach ($records as $r) {
            if ($r->status_approval !== 'disetujui') {
                $k = $label[$r->status_approval] ?? (string) $r->status_approval;
                $hitung[$k] = ($hitung[$k] ?? 0) + 1;
            }
        }

        $bagian = [];
        foreach ($hitung as $k => $n) {
            $bagian[] = "$n $k";
        }

        return implode(', ', $bagian);
    }
}
