<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockModel extends Model
{
    protected $fillable = [
        'inisial_front',
        'titik_produksi',
        'elevasi_genap',
        'elevasi_ganjil',
        'ni_persen',
        'fe_persen',
    ];
}
