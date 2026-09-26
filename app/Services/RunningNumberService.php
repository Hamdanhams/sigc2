<?php

namespace App\Services;

use App\Models\DetailProduksi;

class RunningNumberService
{
    /**
     * Generate running number: 1 huruf (H/K) + 4 angka.
     * Unik per kombinasi Front + huruf prediksi + tahun.
     * $currentItems: baris-baris detail yang masih di form (belum tersimpan),
     * supaya tidak generate nomor yang sama dalam 1 sesi input.
     */
    public function generate(int $frontId, string $huruf, int $tahun, array $currentItems = []): string
    {
        $maxFromCurrent = 0;
        foreach ($currentItems as $item) {
            if (($item['prediksi_kadar'] ?? null) === $huruf && !empty($item['running_number'])) {
                $angka = (int) substr($item['running_number'], 1);
                $maxFromCurrent = max($maxFromCurrent, $angka);
            }
        }

        $lastNumber = DetailProduksi::whereHas('produksi', function ($query) use ($frontId, $tahun) {
            $query->where('front_id', $frontId)
                ->whereYear('tanggal', $tahun);
        })
            ->where('running_number', 'like', $huruf . '%')
            ->orderByDesc('id')
            ->value('running_number');

        $maxFromDb = $lastNumber ? (int) substr($lastNumber, 1) : 0;

        $next = max($maxFromCurrent, $maxFromDb) + 1;

        return $huruf . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
