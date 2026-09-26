<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permintaan extends Model
{
    protected $fillable = [
        'personil_id',
        'front_id',
        'jenis_permintaan',
        'gambar',
        'keterangan',
        'status',
        'hasil_pdf',
    ];

    public function personil()
    {
        return $this->belongsTo(Personil::class);
    }

    public function front()
    {
        return $this->belongsTo(Front::class);
    }
}
