<?php

namespace App\Filament\Resources\FsbsResource\Pages;

use App\Services\CloudinaryCleanupService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Foto plot FSBS di admin: kolom `foto_upload` hanya untuk foto BARU. Kolom `foto_material`
 * (URL Cloudinary) tidak ada di form, jadi menyimpan perubahan lain tidak pernah menghapus foto.
 * Foto baru diunggah ke Cloudinary lalu URL-nya menggantikan yang lama; foto lama di Cloudinary
 * dihapus SETELAH data berhasil tersimpan.
 */
trait UnggahFotoFsbs
{
    protected ?string $fotoLamaUntukDihapus = null;

    protected function unggahFotoFsbs(array $data, ?string $fotoSekarang = null): array
    {
        $path = $data['foto_upload'] ?? null;
        unset($data['foto_upload']);

        if (is_array($path)) {
            $path = reset($path) ?: null;
        }

        if (! $path) {
            return $data;
        }

        try {
            $hasil = Cloudinary::upload(Storage::disk('local')->path($path), ['folder' => 'fsbs']);
            $data['foto_material'] = $hasil->getSecurePath();
            $this->fotoLamaUntukDihapus = $fotoSekarang;
        } catch (\Throwable $e) {
            Notification::make()->title('Gagal mengunggah foto ke Cloudinary')->body($e->getMessage())->danger()->send();
            throw ValidationException::withMessages(['data.foto_upload' => 'Gagal mengunggah foto: ' . $e->getMessage()]);
        } finally {
            Storage::disk('local')->delete($path);
        }

        return $data;
    }

    protected function hapusFotoLamaFsbs(): void
    {
        if ($this->fotoLamaUntukDihapus) {
            app(CloudinaryCleanupService::class)->hapusDariUrl($this->fotoLamaUntukDihapus);
            $this->fotoLamaUntukDihapus = null;
        }
    }
}
