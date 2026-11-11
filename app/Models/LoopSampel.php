<?php

namespace App\Models;

use App\Jobs\HitungEmbeddingLoopJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Sampel referensi fitur Loop: data lab + deskripsi + 1 foto, dan sidik jari visual foto itu
 * (dicocokkan di HP dengan foto yang baru dipotret).
 */
class LoopSampel extends Model
{
    /** Samakan dengan tools/loop/siapkan_paket.py dan model di aplikasi. Ganti bila model diganti. */
    public const MODEL_VERSI = 'dinov2s-int8-v1';

    public const KADAR = ['ni', 'co', 'fe', 'sio2', 'cao', 'mgo', 'cr2o3', 'al2o3', 'cr', 'fe_ni', 's_m', 'bc'];

    protected $fillable = [
        'code', 'kode_sample', 'incr', 'inisial_front', 'zona', 'warna', 'ukuran', 'komposisi', 'keterangan',
        'ni', 'co', 'fe', 'sio2', 'cao', 'mgo', 'cr2o3', 'al2o3', 'cr', 'fe_ni', 's_m', 'bc',
        'foto', 'embedding', 'model_versi', 'status', 'pesan_error',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    protected static function booted(): void
    {
        // Foto diganti tanpa sidik jari baru -> sidik jari lama tidak berlaku lagi, hitung ulang.
        static::saving(function (LoopSampel $s) {
            if ($s->isDirty('foto') && !$s->isDirty('embedding')) {
                $s->embedding = null;
                $s->model_versi = null;
                $s->pesan_error = null;
                $s->status = $s->foto ? 'pending' : 'gagal';
                if (!$s->foto) {
                    $s->pesan_error = 'Belum ada foto';
                }
            }
        });

        static::saved(function (LoopSampel $s) {
            if ($s->wasChanged('foto')) {
                $lama = $s->getOriginal('foto');
                if ($lama && $lama !== $s->foto) {
                    Storage::disk('public')->delete($lama);
                }
            }
            if ($s->status === 'pending' && $s->foto) {
                HitungEmbeddingLoopJob::dispatch($s->id)->afterCommit();
            }
        });

        static::deleted(function (LoopSampel $s) {
            if ($s->foto) {
                Storage::disk('public')->delete($s->foto);
            }
        });
    }
}
