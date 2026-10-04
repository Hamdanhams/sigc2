<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rekonsiliasi extends Model
{
    protected $table = 'rekonsiliasis';

    protected $fillable = [
        'minggu_ke',
        'tanggal_mulai',
        'tanggal_akhir',
        'parameter',
        'bcm_bm',
        'ni_bm',
        'fe_bm',
        'sio2_bm',
        'mgo_bm',
        'bcm_real',
        'ni_real',
        'fe_real',
        'sio2_real',
        'mgo_real',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_akhir' => 'date',
    ];
}
