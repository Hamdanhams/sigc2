<?php

namespace App\Models;

use App\Services\CutiService;
use Illuminate\Database\Eloquent\Model;

class Cuti extends Model
{
    protected $table = 'cutis';

    protected $fillable = [
        'personil_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah_hari',
        'alasan',
        'status',
        'catatan_penolakan',
        'ditolak_oleh_jabatan',
        'disetujui_senior_oleh',
        'disetujui_senior_at',
        'disetujui_wuh_oleh',
        'disetujui_wuh_at',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'disetujui_senior_at' => 'datetime',
        'disetujui_wuh_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Admin menghapus pengajuan yang sudah disetujui -> saldo dikembalikan + dicatat.
        static::deleting(function (Cuti $cuti) {
            if ($cuti->status === 'disetujui') {
                app(CutiService::class)->kembalikanSaldo($cuti);
            }
        });
    }

    public function personil()
    {
        return $this->belongsTo(Personil::class);
    }

    public function penyetujuSenior()
    {
        return $this->belongsTo(UserPegawai::class, 'disetujui_senior_oleh');
    }

    public function penyetujuWuh()
    {
        return $this->belongsTo(UserPegawai::class, 'disetujui_wuh_oleh');
    }
}
