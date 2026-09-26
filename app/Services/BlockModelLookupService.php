<?php

namespace App\Services;

use App\Models\BlockModel;

class BlockModelLookupService
{
    public function lookup(?string $front, ?string $titikProduksi, ?string $elevasi): ?array
    {
        if (!$front || !$titikProduksi || !$elevasi) {
            return null;
        }

        $target = $this->normalizeElevasi($elevasi);

        $blockModel = BlockModel::where('inisial_front', $front)
            ->where('titik_produksi', $titikProduksi)
            ->get()
            ->first(function ($bm) use ($target) {
                return $this->normalizeElevasi($bm->elevasi_genap) === $target
                    || $this->normalizeElevasi($bm->elevasi_ganjil) === $target;
            });

        if (!$blockModel) {
            return null;
        }

        return [
            'ni_bm' => $blockModel->ni_persen,
            'fe_bm' => $blockModel->fe_persen,
        ];
    }

    /**
     * Normalisasi elevasi supaya "010" dan "10" dianggap sama.
     * Format M+2digit (nilai minus) tetap dipertahankan dengan prefix M.
     */
    protected function normalizeElevasi(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = strtoupper(trim($value));

        if (str_starts_with($value, 'M')) {
            $digits = ltrim(substr($value, 1), '0');
            return 'M' . ($digits === '' ? '0' : $digits);
        }

        return ltrim($value, '0') ?: '0';
    }
}
