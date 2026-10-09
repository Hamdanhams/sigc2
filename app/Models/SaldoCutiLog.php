<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoCutiLog extends Model
{
    protected $table = 'saldo_cuti_logs';

    protected $fillable = [
        'personil_id',
        'perubahan',
        'saldo_sebelum',
        'saldo_sesudah',
        'jenis',
        'cuti_id',
        'catatan',
        'oleh',
    ];

    public function personil()
    {
        return $this->belongsTo(Personil::class);
    }
}
