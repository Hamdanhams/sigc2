<?php

namespace App\Filament\Resources\LoginSlideResource\Pages;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Unggah foto slide ke Cloudinary lalu simpan URL-nya di kolom `gambar`. */
trait UnggahGambarSlide
{
    protected function unggahKeCloudinary(array $data, bool $wajib): array
    {
        $path = $data['gambar_upload'] ?? null;
        unset($data['gambar_upload']);

        if (is_array($path)) {
            $path = reset($path) ?: null;
        }

        if (! $path) {
            if ($wajib) {
                throw ValidationException::withMessages(['data.gambar_upload' => 'Foto wajib diunggah.']);
            }

            return $data;
        }

        try {
            $hasil = Cloudinary::upload(Storage::disk('local')->path($path), [
                'folder' => 'login-slides',
            ]);
            $data['gambar'] = $hasil->getSecurePath();
        } catch (\Throwable $e) {
            Notification::make()->title('Gagal mengunggah foto ke Cloudinary')->body($e->getMessage())->danger()->send();
            throw ValidationException::withMessages(['data.gambar_upload' => 'Gagal mengunggah foto.']);
        } finally {
            Storage::disk('local')->delete($path);
        }

        return $data;
    }
}
