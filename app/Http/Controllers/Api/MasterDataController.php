<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Front;
use App\Models\Personil;
use App\Models\UserPegawai;

class MasterDataController extends Controller
{
    public function fronts()
    {
        return response()->json(
            Front::where('status', 'active')->orderBy('nama_front')->get(['id', 'nama_front', 'inisial'])
        );
    }

    public function personils()
    {
        return response()->json(
            Personil::orderBy('nama')->get(['id', 'nama', 'inisial'])
        );
    }

    public function userPegawais()
    {
        return response()->json(
            UserPegawai::where('status', 'active')->orderBy('nama')->get(['id', 'nama', 'npp'])
        );
    }
}
