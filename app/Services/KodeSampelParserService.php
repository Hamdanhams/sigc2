<?php

namespace App\Services;

class KodeSampelParserService
{
    /**
     * Parsing kode sampel FSBS, contoh: RB4037A008A, RB41143C012A
     * - 3 karakter depan → Front
     * - 1 karakter terakhir → Huruf running
     * - 3 karakter sebelum huruf terakhir → Elevasi (termasuk format M+2digit untuk minus)
     * - Sisa di tengah → Titik Produksi (panjang fleksibel)
     */
    public function parse(string $kodeSampel): array
    {
        $kode = strtoupper(trim($kodeSampel));
        $panjang = strlen($kode);

        // Minimal 7 karakter (3 front + minimal 1 titik produksi + 3 elevasi) + 1 huruf = 8
        if ($panjang < 8) {
            return [
                'front' => null,
                'titik_produksi' => null,
                'elevasi' => null,
                'huruf_running' => null,
                'valid' => false,
            ];
        }

        $front = substr($kode, 0, 3);
        $hurufRunning = substr($kode, -1);
        $elevasi = substr($kode, -4, 3);
        $titikProduksi = substr($kode, 3, $panjang - 7);

        return [
            'front' => $front,
            'titik_produksi' => $titikProduksi,
            'elevasi' => $elevasi,
            'huruf_running' => $hurufRunning,
            'valid' => true,
        ];
    }
}
