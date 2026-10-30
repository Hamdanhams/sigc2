<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PetaBlockModel;
use App\Models\SebaranFsbs;

class PetaBlockModelController extends Controller
{
    /** Daftar peta Block Model yang sudah selesai dikonversi (aplikasi menyaring per Front). */
    public function index()
    {
        $peta = PetaBlockModel::where('status', 'selesai')
            ->with('front')
            ->orderBy('front_id')
            ->orderBy('rl')
            ->orderBy('nama_bagian')
            ->get()
            ->map(fn(PetaBlockModel $p) => [
                'id' => $p->id,
                'nama_peta' => $p->label,
                'front' => $p->front?->nama_front,
                'front_id' => $p->front_id,
                'rl' => $p->rl,
                'nama_bagian' => $p->nama_bagian,
                'elevasi_data' => $p->elevasi_data,
                'download_url' => route('api.peta-block-model.download', $p->id),
                'center_lat' => $p->center_lat,
                'center_lon' => $p->center_lon,
                'min_zoom' => $p->min_zoom,
                'max_zoom' => $p->max_zoom,
                'updated_at' => $p->updated_at,
            ]);

        return response()->json($peta);
    }

    public function download(int $id)
    {
        $peta = PetaBlockModel::with('front')->findOrFail($id);

        if ($peta->status !== 'selesai' || !$peta->file_mbtiles) {
            return response()->json(['message' => 'File belum siap'], 404);
        }

        $path = storage_path('app/public/' . $peta->file_mbtiles);

        if (!file_exists($path)) {
            return response()->json(['message' => 'File tidak ditemukan'], 404);
        }

        return response()->download($path, 'blockmodel-' . $peta->id . '.zip');
    }

    /**
     * Titik sebaran FSBS untuk peta ini: inisial Front yang sama dan elevasi = RL + 2.
     * Semua titik Front+elevasi itu dikirim (peta bagian Utara/Tengah/Selatan memakai data yang sama);
     * titik di luar area peta yang dibuka tidak tampak di layar. Format sama dengan /sebaran-fsbs/{front}.
     */
    public function sebaran(int $id)
    {
        $peta = PetaBlockModel::with('front')->findOrFail($id);
        $inisial = $peta->front?->inisial;

        if (!$inisial) {
            return response()->json([]);
        }

        $rows = SebaranFsbs::where('inisial_front', $inisial)
            // hanya elevasi berupa angka (format "-02" / "14"); nilai lain tidak ikut tercocok sebagai 0
            ->whereRaw("elevasi REGEXP '^-?[0-9]+(\\\\.[0-9]+)?$'")
            ->whereRaw('CAST(elevasi AS DECIMAL(10,2)) = ?', [$peta->elevasi_data])
            ->get();

        $result = $rows
            ->groupBy(fn($r) => $r->koordinat_x . '_' . $r->koordinat_y . '_' . $r->titik_produksi)
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'titik_produksi' => $first->titik_produksi,
                    'koordinat_x' => (float) $first->koordinat_x,
                    'koordinat_y' => (float) $first->koordinat_y,
                    'data' => $group->map(fn($r) => [
                        'elevasi' => $r->elevasi,
                        'ni' => $r->ni,
                        'fe' => $r->fe,
                        'si_mg_ratio' => $r->si_mg_ratio,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json($result);
    }
}
