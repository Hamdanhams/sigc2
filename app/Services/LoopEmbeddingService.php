<?php

namespace App\Services;

use App\Models\LoopSampel;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Menghitung sidik jari visual foto sampel Loop lewat skrip Python (ONNX Runtime). */
class LoopEmbeddingService
{
    /** Hitung dan simpan sidik jari satu sampel. Status jadi 'siap' atau 'gagal'. */
    public function hitung(LoopSampel $sampel): void
    {
        try {
            $vektor = $this->embeddingFoto($sampel);
            $sampel->forceFill([
                'embedding' => $vektor,
                'model_versi' => LoopSampel::MODEL_VERSI,
                'status' => 'siap',
                'pesan_error' => null,
            ])->saveQuietly();
        } catch (\Throwable $e) {
            $sampel->forceFill([
                'status' => 'gagal',
                'pesan_error' => mb_substr($e->getMessage(), 0, 1000),
            ])->saveQuietly();
        }
    }

    /** @return float[] */
    public function embeddingFoto(LoopSampel $sampel): array
    {
        if (!$sampel->foto || !Storage::disk('public')->exists($sampel->foto)) {
            throw new RuntimeException('File foto tidak ditemukan');
        }
        if (!is_file(config('loop.model'))) {
            throw new RuntimeException('Model Loop belum ada di ' . config('loop.model'));
        }

        $hasil = Process::timeout(config('loop.timeout'))->run([
            config('loop.python'),
            config('loop.skrip'),
            config('loop.model'),
            Storage::disk('public')->path($sampel->foto),
        ]);

        if (!$hasil->successful()) {
            throw new RuntimeException('Python gagal: ' . trim($hasil->errorOutput() ?: $hasil->output()));
        }

        $vektor = json_decode(trim($hasil->output()), true);
        if (!is_array($vektor) || count($vektor) < 100) {
            throw new RuntimeException('Keluaran sidik jari tidak valid');
        }

        return $vektor;
    }
}
