<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Front extends Model
{
    protected $fillable = [
        'nama_front',
        'inisial',
        'lokasi',
        'status',
    ];
}
