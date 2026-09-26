<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SebaranFsbs extends Model
{
    protected $table = 'sebaran_fsbs';

    protected $fillable = [
        'inisial_front',
        'titik_produksi',
        'elevasi',
        'koordinat_x',
        'koordinat_y',
        'ni',
        'fe',
        'si_mg_ratio',
    ];
}
