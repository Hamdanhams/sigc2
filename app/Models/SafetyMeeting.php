<?php

namespace App\Models;

use App\Services\CloudinaryCleanupService;
use Illuminate\Database\Eloquent\Model;

class SafetyMeeting extends Model
{
    protected $fillable = [
        'client_uuid',
        'user_pegawai_id',
        'waktu',
        'lokasi',
        'anggota',
        'pembahasan',
        'foto',
    ];

    protected $casts = [
        'waktu' => 'datetime',
        'anggota' => 'array',
    ];

    protected static function booted(): void
    {
        // Data dihapus (admin) -> foto di Cloudinary ikut dihapus agar tidak menumpuk.
        static::deleted(function (SafetyMeeting $sm) {
            app(CloudinaryCleanupService::class)->hapusDariUrl($sm->foto);
        });
    }

    public function pembuat()
    {
        return $this->belongsTo(UserPegawai::class, 'user_pegawai_id');
    }
}
