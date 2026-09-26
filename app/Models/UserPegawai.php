<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class UserPegawai extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'nama',
        'npp',
        'ttd',
        'status',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
    ];
}
