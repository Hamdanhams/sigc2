<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;

/**
 * Menghapus file lama di Cloudinary supaya tidak menumpuk (mis. saat foto diganti).
 * Gagal menghapus tidak boleh menggagalkan proses utama: hanya dicatat di log.
 */
class CloudinaryCleanupService
{
    /** Ambil public_id dari URL Cloudinary, atau null kalau bukan URL Cloudinary. */
    public function publicIdDariUrl(?string $url): ?string
    {
        if (!$url || !str_contains((string) parse_url($url, PHP_URL_HOST), 'res.cloudinary.com')) {
            return null;
        }

        // .../image/upload/v1700000000/folder/nama.jpg -> folder/nama
        if (preg_match('#/upload/(?:v\d+/)?(.+?)(?:\.[A-Za-z0-9]+)?$#', (string) parse_url($url, PHP_URL_PATH), $m)) {
            return urldecode($m[1]);
        }

        return null;
    }

    public function hapusDariUrl(?string $url): void
    {
        $publicId = $this->publicIdDariUrl($url);
        if ($publicId === null) {
            return;
        }

        try {
            Cloudinary::destroy($publicId);
        } catch (\Throwable $e) {
            Log::warning("Gagal menghapus file Cloudinary [$publicId]: " . $e->getMessage());
        }
    }
}
