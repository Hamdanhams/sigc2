<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetaBlockModel extends Model
{
    /** Subfolder hasil ZIP di storage/app/public (jangan dipakai bersama jenis peta lain). */
    public const FOLDER = 'mbtiles_blockmodel';

    /** Data sebaran yang diplot = elevasi RL + selisih ini. */
    public const SELISIH_ELEVASI = 2;

    protected $fillable = [
        'front_id',
        'rl',
        'nama_bagian',
        'file_geopdf',
        'file_mbtiles',
        'status',
        'pesan_error',
        'center_lat',
        'center_lon',
        'min_zoom',
        'max_zoom',
    ];

    protected $casts = [
        'rl' => 'float',
    ];

    public function front()
    {
        return $this->belongsTo(Front::class);
    }

    /** Contoh: "RB4 RL 12 (Utara)". */
    public function getLabelAttribute(): string
    {
        $rl = rtrim(rtrim(number_format((float) $this->rl, 2, '.', ''), '0'), '.');
        $label = trim(($this->front?->inisial ?? $this->front?->nama_front ?? '') . ' RL ' . $rl);

        return $this->nama_bagian ? "{$label} ({$this->nama_bagian})" : $label;
    }

    public function getElevasiDataAttribute(): float
    {
        return (float) $this->rl + self::SELISIH_ELEVASI;
    }

    protected static function booted(): void
    {
        // GeoPDF diganti -> hasil konversi lama tidak berlaku lagi.
        static::updating(function (PetaBlockModel $p) {
            if ($p->isDirty('file_geopdf')) {
                self::hapusFile($p->getOriginal('file_geopdf'), $p->getOriginal('file_mbtiles'));
                $p->file_mbtiles = null;
                $p->status = 'pending';
                $p->pesan_error = null;
            }
        });

        static::deleting(function (PetaBlockModel $p) {
            self::hapusFile($p->file_geopdf, $p->file_mbtiles);
        });
    }

    private static function hapusFile(?string $geopdf, ?string $zip): void
    {
        if ($geopdf) {
            $path = storage_path('app/private/' . $geopdf);
            @unlink($path);
            @unlink($path . '.aux.xml');
        }
        if ($zip) {
            @unlink(storage_path('app/public/' . $zip));
        }
    }
}
