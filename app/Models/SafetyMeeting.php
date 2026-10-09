<?php

namespace App\Models;

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

    public function pembuat()
    {
        return $this->belongsTo(UserPegawai::class, 'user_pegawai_id');
    }
}
