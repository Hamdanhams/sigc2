<?php

namespace App\Models;

use App\Services\CloudinaryCleanupService;
use Illuminate\Database\Eloquent\Model;

class LoginSlide extends Model
{
    protected $fillable = ['gambar', 'judul', 'keterangan', 'urutan', 'aktif'];

    protected $casts = [
        'aktif' => 'boolean',
        'urutan' => 'integer',
    ];

    protected static function booted(): void
    {
        // Foto diganti -> foto lama di Cloudinary dihapus.
        static::updated(function (LoginSlide $slide) {
            if ($slide->wasChanged('gambar')) {
                app(CloudinaryCleanupService::class)->hapusDariUrl($slide->getOriginal('gambar'));
            }
        });

        // Slide dihapus -> fotonya ikut dihapus.
        static::deleted(function (LoginSlide $slide) {
            app(CloudinaryCleanupService::class)->hapusDariUrl($slide->gambar);
        });
    }
}
