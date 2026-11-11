<?php

namespace App\Console\Commands;

use App\Models\LoopSampel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Impor paket data Loop buatan tools/loop/siapkan_paket.py (sampel.json + foto/).
 * Sampel dengan code yang sama diperbarui. Sidik jari ikut dari paket, jadi tidak butuh Python.
 */
class ImporLoop extends Command
{
    protected $signature = 'loop:impor {paket : Folder paket (berisi sampel.json dan foto/)}';

    protected $description = 'Impor paket data Loop (sampel, foto, sidik jari)';

    public function handle(): int
    {
        $folder = rtrim($this->argument('paket'), '/\\');
        $berkas = $folder . DIRECTORY_SEPARATOR . 'sampel.json';

        if (!is_file($berkas)) {
            $this->error("sampel.json tidak ditemukan di {$folder}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($berkas), true);
        if (!is_array($data)) {
            $this->error('sampel.json tidak valid');

            return self::FAILURE;
        }

        $baru = $diperbarui = $tanpaFoto = 0;
        $kolom = array_merge(
            ['kode_sample', 'incr', 'inisial_front', 'zona', 'warna', 'ukuran', 'komposisi', 'keterangan'],
            LoopSampel::KADAR
        );

        foreach ($data as $item) {
            $sampel = LoopSampel::firstOrNew(['code' => $item['code']]);
            $ada = $sampel->exists;

            foreach ($kolom as $k) {
                $sampel->{$k} = $item[$k] ?? null;
            }

            $sumber = !empty($item['foto']) ? $folder . DIRECTORY_SEPARATOR . $item['foto'] : null;
            if ($sumber && is_file($sumber) && !empty($item['embedding'])) {
                $tujuan = 'loop/' . $item['code'] . '.jpg';
                Storage::disk('public')->put($tujuan, file_get_contents($sumber), 'public');
                $sampel->foto = $tujuan;
                $sampel->embedding = $item['embedding'];
                $sampel->model_versi = $item['model_versi'] ?? LoopSampel::MODEL_VERSI;
                $sampel->status = 'siap';
                $sampel->pesan_error = null;
            } else {
                $tanpaFoto++;
                if (!$ada) {
                    $sampel->status = 'gagal';
                    $sampel->pesan_error = 'Belum ada foto';
                }
            }

            $sampel->save();
            $ada ? $diperbarui++ : $baru++;
        }

        $this->info("Selesai: {$baru} baru, {$diperbarui} diperbarui, {$tanpaFoto} tanpa foto/sidik jari.");

        return self::SUCCESS;
    }
}
