<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SectionDokumen extends Model
{
    protected $fillable = ['front_id', 'judul', 'file_pdf', 'ukuran'];

    public function front(): BelongsTo
    {
        return $this->belongsTo(Front::class);
    }

    protected static function booted(): void
    {
        // Ukuran file dicatat otomatis supaya aplikasi bisa menampilkannya.
        static::saving(function (SectionDokumen $d) {
            if ($d->file_pdf && Storage::disk('local')->exists($d->file_pdf)) {
                $d->ukuran = Storage::disk('local')->size($d->file_pdf);
            }
        });

        // Ganti file -> file lama dihapus.
        static::updated(function (SectionDokumen $d) {
            $lama = $d->getOriginal('file_pdf');
            if ($lama && $lama !== $d->file_pdf) {
                Storage::disk('local')->delete($lama);
            }
        });

        static::deleted(function (SectionDokumen $d) {
            if ($d->file_pdf) {
                Storage::disk('local')->delete($d->file_pdf);
            }
        });
    }
}
