<?php

namespace App\Filament\Resources\ProduksiResource\Pages;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Foto baru dari kolom "tambah ..." diunggah ke Cloudinary lalu DIGABUNG ke daftar URL
 * dokumentasi yang sudah ada (daftar lama tidak pernah ditimpa). Dipakai di halaman
 * Buat dan Edit supaya yang tersimpan selalu URL (sama seperti dari aplikasi).
 */
trait GabungDokumentasi
{
    protected function gabungDokumentasi(array $data, ?\App\Models\Produksi $record = null): array
    {
        foreach ([
            'tambah_dokumentasi_produksi' => ['dokumentasi_produksi', 'produksi'],
            'tambah_dokumentasi_kendala' => ['dokumentasi_kendala', 'kendala'],
        ] as $kolomTambah => [$kolom, $folder]) {
            $paths = array_values((array) ($data[$kolomTambah] ?? []));
            unset($data[$kolomTambah]);

            if ($paths === []) {
                continue;
            }

            $urlBaru = [];
            foreach ($paths as $path) {
                try {
                    $hasil = Cloudinary::upload(Storage::disk('local')->path($path), ['folder' => $folder]);
                    $urlBaru[] = $hasil->getSecurePath();
                } catch (\Throwable $e) {
                    Notification::make()->title('Gagal mengunggah foto ke Cloudinary')->body($e->getMessage())->danger()->send();
                    throw ValidationException::withMessages(["data.$kolomTambah" => 'Gagal mengunggah foto: ' . $e->getMessage()]);
                } finally {
                    Storage::disk('local')->delete($path);
                }
            }

            $lama = $record ? (array) $record->{$kolom} : [];
            $data[$kolom] = array_values(array_merge($lama, $urlBaru));
        }

        return $data;
    }
}
