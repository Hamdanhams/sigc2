<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetaLayer extends Model
{
    protected $fillable = [
        'nama_peta',
        'front_id',
        'file_geopdf',
        'file_mbtiles',
        'status',
        'pesan_error',
        'center_lat',
        'center_lon',
        'min_zoom',
        'max_zoom',
    ];

    public function front()
    {
        return $this->belongsTo(Front::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($peta) {
            if ($peta->file_geopdf) {
                $geopdfPath = storage_path('app/private/' . $peta->file_geopdf);

                if (file_exists($geopdfPath)) {
                    @unlink($geopdfPath);
                }

                $auxXmlPath = $geopdfPath . '.aux.xml';
                if (file_exists($auxXmlPath)) {
                    @unlink($auxXmlPath);
                }
            }

            if ($peta->file_mbtiles) {
                $zipPath = storage_path('app/public/' . $peta->file_mbtiles);
                if (file_exists($zipPath)) {
                    @unlink($zipPath);
                }
            }
        });
    }
}
