<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produksi extends Model
{
    protected $fillable = [
        'tanggal',
        'shift',
        'jam_mulai',
        'jam_selesai',
        'front_id',
        'fleet',
        'pic_1_id',
        'pic_2_id',
        'user_pegawai_id',
        'dokumentasi_produksi',
        'dokumentasi_kendala',
        'keterangan',
        'rencana_produksi_besok',
        'status_approval',
        'catatan_penolakan',
        'ditolak_oleh_jabatan',
    ];

    protected $casts = [
        'dokumentasi_produksi' => 'array',
        'dokumentasi_kendala' => 'array',
    ];

    protected static function booted(): void
    {
        // Laporan dihapus (admin, satuan/massal) -> semua foto dokumentasinya ikut dihapus dari
        // Cloudinary. Penghapusan berantai dari database (mis. user dihapus) TIDAK memicu ini.
        static::deleted(function (Produksi $p) {
            app(\App\Services\CloudinaryCleanupService::class)->hapusBanyakDariUrl(array_merge(
                (array) $p->dokumentasi_produksi,
                (array) $p->dokumentasi_kendala,
            ));
        });
    }

    public function front()
    {
        return $this->belongsTo(Front::class);
    }

    public function pic1()
    {
        return $this->belongsTo(Personil::class, 'pic_1_id');
    }

    public function pic2()
    {
        return $this->belongsTo(Personil::class, 'pic_2_id');
    }

    public function userPegawai()
    {
        return $this->belongsTo(UserPegawai::class);
    }

    public function details()
    {
        return $this->hasMany(DetailProduksi::class);
    }
}
