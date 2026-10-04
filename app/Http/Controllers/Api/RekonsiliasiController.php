<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RekonsiliasiService;

class RekonsiliasiController extends Controller
{
    /**
     * Seluruh periode beserta nilai BM vs Real per Parameter dan Jenis
     * (termasuk Total ORE yang dihitung). Satu payload agar mudah di-cache offline.
     */
    public function index(RekonsiliasiService $service)
    {
        return response()->json([
            'jenis' => RekonsiliasiService::JENIS,
            'parameter' => RekonsiliasiService::PARAMETER,
            'periode' => $service->semuaPeriode(),
        ]);
    }
}
