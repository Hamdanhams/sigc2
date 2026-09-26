<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailProduksi extends Model
{
    protected $fillable = [
        'produksi_id',
        'titik_produksi',
        'elevasi_atas',
        'running_number',
        'tujuan_dumping',
        'ni_bm',
        'fe_bm',
        'ritase',
        'gridding',
    ];

    public function produksi()
    {
        return $this->belongsTo(Produksi::class);
    }
}
