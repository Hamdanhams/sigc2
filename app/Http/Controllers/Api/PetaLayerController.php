<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PetaLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PetaLayerController extends Controller
{
    /**
     * List semua peta yang siap dipakai (status selesai).
     */
    public function index(Request $request)
    {
        $peta = PetaLayer::where('status', 'selesai')
            ->with('front')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nama_peta' => $p->nama_peta,
                    'front' => $p->front?->nama_front,
                    'front_id' => $p->front_id,
                    'download_url' => route('api.peta.download', $p->id),
                    'center_lat' => $p->center_lat,
                    'center_lon' => $p->center_lon,
                    'min_zoom' => $p->min_zoom,
                    'max_zoom' => $p->max_zoom,
                    'updated_at' => $p->updated_at,
                ];
            });

        return response()->json($peta);
    }

    /**
     * Download file MBTiles.
     */
    public function download(int $id)
    {
        $peta = PetaLayer::findOrFail($id);

        if ($peta->status !== 'selesai' || !$peta->file_mbtiles) {
            return response()->json(['message' => 'File belum siap'], 404);
        }

        $path = storage_path('app/public/' . $peta->file_mbtiles);

        if (!file_exists($path)) {
            return response()->json(['message' => 'File tidak ditemukan'], 404);
        }

        return response()->download($path, $peta->nama_peta . '.zip');
    }
}
