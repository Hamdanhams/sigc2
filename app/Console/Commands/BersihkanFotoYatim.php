<?php

namespace App\Console\Commands;

use App\Services\CloudinaryCleanupService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mencari (dan, hanya dengan --hapus, menghapus) foto di Cloudinary yang tidak lagi dipakai
 * data mana pun di database. WAJIB dijalankan di server yang database-nya berisi data asli.
 */
class BersihkanFotoYatim extends Command
{
    protected $signature = 'cloudinary:bersihkan-yatim
        {--hapus : Benar-benar menghapus (tanpa opsi ini hanya melaporkan)}
        {--umur=3 : Abaikan foto yang lebih muda dari sekian hari (melindungi sinkron yang sedang berjalan)}
        {--folder=* : Folder yang diperiksa (default: fsbs, produksi, kendala)}
        {--paksa : Abaikan pengaman rasio yatim terlalu besar}
        {--detail : Tampilkan SEMUA foto yatim beserta waktu unggah (WITA), ukuran, dan URL}';

    protected $description = 'Laporkan/hapus foto Cloudinary yang tidak dirujuk database (default: hanya laporan)';

    public function handle(CloudinaryCleanupService $cleanup): int
    {
        $folders = $this->option('folder') ?: ['fsbs', 'produksi', 'kendala'];
        $umur = max(1, (int) $this->option('umur'));
        $batas = now()->subDays($umur);

        $this->info('Mengumpulkan foto yang dirujuk database...');
        $dirujuk = $this->kumpulkanRujukan($cleanup);
        $this->line('  Foto dirujuk: ' . count($dirujuk));

        if (count($dirujuk) === 0) {
            $this->error('Tidak ada satu pun rujukan foto di database. Ini bukan database produksi? Dibatalkan.');

            return self::FAILURE;
        }

        $this->info('Membaca isi Cloudinary (folder: ' . implode(', ', $folders) . ')...');
        $semua = [];
        foreach ($folders as $folder) {
            $kursor = null;
            do {
                $opsi = ['type' => 'upload', 'prefix' => rtrim($folder, '/') . '/', 'max_results' => 500];
                if ($kursor) {
                    $opsi['next_cursor'] = $kursor;
                }
                $hasil = Cloudinary::admin()->assets($opsi);
                foreach ($hasil['resources'] ?? [] as $r) {
                    $semua[$r['public_id']] = $r;
                }
                $kursor = $hasil['next_cursor'] ?? null;
            } while ($kursor);
        }

        $yatim = [];
        $terlaluBaru = 0;
        $byteYatim = 0;
        foreach ($semua as $id => $r) {
            if (isset($dirujuk[$id])) {
                continue;
            }
            if (isset($r['created_at']) && \Carbon\Carbon::parse($r['created_at'])->greaterThan($batas)) {
                $terlaluBaru++;

                continue;
            }
            $yatim[] = $id;
            $byteYatim += (int) ($r['bytes'] ?? 0);
        }

        $total = count($semua);
        $this->newLine();
        $this->line("Total foto di Cloudinary (folder diperiksa) : {$total}");
        $this->line('Dipakai data di database                    : ' . ($total - count($yatim) - $terlaluBaru));
        $this->line("Dilewati karena lebih muda dari {$umur} hari : {$terlaluBaru}");
        $this->line('YATIM (tidak dirujuk, cukup tua)            : ' . count($yatim) . ' (~' . round($byteYatim / 1048576, 1) . ' MB)');

        if ($this->option('detail')) {
            // Urut waktu unggah, supaya mudah dicocokkan dengan data yang kehilangan tautan foto.
            usort($yatim, fn($a, $b) => strcmp($semua[$a]['created_at'] ?? '', $semua[$b]['created_at'] ?? ''));
            foreach ($yatim as $id) {
                $waktu = isset($semua[$id]['created_at'])
                    ? \Carbon\Carbon::parse($semua[$id]['created_at'])->timezone('Asia/Makassar')->format('d/m H:i:s')
                    : '-';
                $this->line(sprintf('  %s WITA | %4d KB | %s', $waktu, round(($semua[$id]['bytes'] ?? 0) / 1024), $semua[$id]['secure_url'] ?? $id));
            }
        } else {
            foreach (array_slice($yatim, 0, 15) as $id) {
                $this->line("  - {$id}");
            }
            if (count($yatim) > 15) {
                $this->line('  ... dan ' . (count($yatim) - 15) . ' lainnya (pakai --detail untuk semuanya)');
            }
        }

        if (! $yatim) {
            $this->info('Tidak ada foto yatim.');

            return self::SUCCESS;
        }

        // Pengaman: kalau hampir semua foto dianggap yatim, hampir pasti ada yang salah
        // (database salah / kolom rujukan terlewat), bukan memang sebanyak itu.
        if ($total > 0 && count($yatim) / $total > 0.5 && ! $this->option('paksa')) {
            $this->error('Lebih dari 50% foto dianggap yatim. Itu mencurigakan; tidak ada yang dihapus. Periksa dulu (atau pakai --paksa jika yakin).');

            return self::FAILURE;
        }

        if (! $this->option('hapus')) {
            $this->warn('MODE LAPORAN: belum ada yang dihapus. Jalankan lagi dengan --hapus untuk menghapus.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Hapus PERMANEN ' . count($yatim) . ' foto di atas dari Cloudinary? Tidak bisa dibatalkan.')) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        $dihapus = 0;
        foreach (array_chunk($yatim, 100) as $kelompok) {
            Cloudinary::admin()->deleteAssets($kelompok);
            $dihapus += count($kelompok);
            $this->line("  dihapus {$dihapus}/" . count($yatim));
        }
        $this->info('Selesai.');

        return self::SUCCESS;
    }

    /**
     * Semua public_id yang dirujuk tabel mana pun yang menyimpan foto Cloudinary.
     * Nilai yang bukan URL (path lama dari komponen unggah Filament) ikut dilindungi.
     *
     * @return array<string, true>
     */
    private function kumpulkanRujukan(CloudinaryCleanupService $cleanup): array
    {
        $nilai = [];

        // kolom teks tunggal
        foreach ([
            ['fsbs', 'foto_material'],
            ['safety_meetings', 'foto'],
            ['permintaans', 'gambar'],
            ['login_slides', 'gambar'],
        ] as [$tabel, $kolom]) {
            foreach (DB::table($tabel)->whereNotNull($kolom)->pluck($kolom) as $v) {
                $nilai[] = (string) $v;
            }
        }

        // kolom JSON berisi daftar
        foreach (['dokumentasi_produksi', 'dokumentasi_kendala'] as $kolom) {
            foreach (DB::table('produksis')->whereNotNull($kolom)->pluck($kolom) as $json) {
                foreach ((array) json_decode((string) $json, true) as $v) {
                    $nilai[] = (string) $v;
                }
            }
        }

        $id = [];
        foreach ($nilai as $v) {
            $v = trim($v);
            if ($v === '') {
                continue;
            }
            $publik = $cleanup->publicIdDariUrl($v);
            if ($publik !== null) {
                $id[$publik] = true;
            }
            // path mentah (tanpa ekstensi) sebagai pelindung tambahan
            $id[preg_replace('/\.[A-Za-z0-9]+$/', '', ltrim($v, '/'))] = true;
        }

        return $id;
    }
}
