<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SebaranFsbs;
use Illuminate\Http\Request;

class SebaranFsbsController extends Controller
{
    /**
     * Ambil semua data sebaran untuk 1 Front, dikelompokkan per titik koordinat.
     */
    public function byFront(Request $request, string $front)
    {
        $rows = SebaranFsbs::where('inisial_front', $front)->get();

        // Kelompokkan berdasarkan koordinat + titik produksi yang sama
        $grouped = $rows->groupBy(function ($row) {
            return $row->koordinat_x . '_' . $row->koordinat_y . '_' . $row->titik_produksi;
        });

        $result = $grouped->map(function ($group) {
            $first = $group->first();
            return [
                'titik_produksi' => $first->titik_produksi,
                'koordinat_x' => (float) $first->koordinat_x,
                'koordinat_y' => (float) $first->koordinat_y,
                'data' => $group->map(function ($row) {
                    return [
                        'elevasi' => $row->elevasi,
                        'ni' => $row->ni,
                        'fe' => $row->fe,
                        'si_mg_ratio' => $row->si_mg_ratio,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json($result);
    }
}
