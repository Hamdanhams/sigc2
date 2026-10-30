<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fsbs extends Model
{
    protected $table = 'fsbs';

    protected $fillable = [
        'kode_sampel',
        'client_uuid',
        'front',
        'titik_produksi',
        'elevasi',
        'huruf_running',
        'koordinat_x',
        'koordinat_y',
        'personil_id',
        'foto_material',
        'keterangan',
        'increment',
        'gridding',
        'ni_bm',
        'fe_bm',
        'ni',
        'user_pegawai_id',
        'status_approval',
        'catatan_penolakan',
        'ditolak_oleh_jabatan',
    ];

    /** Zona waktu lapangan. created_at disimpan UTC, tanggal kerja dihitung WITA. */
    public const ZONA = 'Asia/Makassar';

    /**
     * Batasi ke data yang dibuat pada rentang tanggal (inklusif) menurut WITA.
     * Memakai rentang waktu (bukan whereDate) supaya index created_at terpakai.
     */
    protected static function booted(): void
    {
        // Data dihapus (admin, satuan/massal) -> foto plot ikut dihapus dari Cloudinary.
        static::deleted(function (Fsbs $f) {
            app(\App\Services\CloudinaryCleanupService::class)->hapusBanyakDariUrl([$f->foto_material]);
        });
    }

    public function scopeTanggalWita($query, string $dari, ?string $sampai = null)
    {
        $awal = \Carbon\Carbon::parse($dari, self::ZONA)->startOfDay()->utc();
        $akhir = \Carbon\Carbon::parse($sampai ?? $dari, self::ZONA)->endOfDay()->utc();

        return $query->where('created_at', '>=', $awal)->where('created_at', '<=', $akhir);
    }

    public function personil()
    {
        return $this->belongsTo(Personil::class);
    }

    public function userPegawai()
    {
        return $this->belongsTo(UserPegawai::class);
    }
}
