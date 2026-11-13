<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Jam kerja tiap shift (diatur admin di menu Pengaturan), dipakai di PDF laporan Produksi. */
class JamKerjaShift extends Model
{
    protected $fillable = ['shift', 'jam_mulai', 'jam_selesai'];

    /** Contoh: "07:00 - 15:00"; null bila shift belum diatur. */
    public static function teksUntukShift(?string $shift): ?string
    {
        if ($shift === null) {
            return null;
        }

        $baris = static::where('shift', $shift)->first();

        if (!$baris || !$baris->jam_mulai || !$baris->jam_selesai) {
            return null;
        }

        return substr($baris->jam_mulai, 0, 5) . ' - ' . substr($baris->jam_selesai, 0, 5);
    }
}
