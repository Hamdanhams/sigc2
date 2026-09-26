<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fsbs extends Model
{
    protected $table = 'fsbs';

    protected $fillable = [
        'kode_sampel',
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
    ];

    public function personil()
    {
        return $this->belongsTo(Personil::class);
    }
}
