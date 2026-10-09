<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Personil extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'nama',
        'id_personil',
        'inisial',
        'saldo_cuti',
        'ttd',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}
