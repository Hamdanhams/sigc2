<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permintaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermintaanController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'front_id' => 'required|exists:fronts,id',
            'jenis_permintaan' => 'required|string',
            'gambar' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $personil = $request->user();

        $permintaan = Permintaan::create([
            'personil_id' => $personil->id,
            'front_id' => $request->front_id,
            'jenis_permintaan' => strtoupper($request->jenis_permintaan),
            'gambar' => $request->gambar,
            'keterangan' => strtoupper($request->keterangan ?? ''),
            'status' => 'menunggu',
        ]);

        return response()->json(['message' => 'Permintaan berhasil diajukan', 'data' => $permintaan], 201);
    }

    public function index(Request $request)
    {
        $personil = $request->user();

        $list = Permintaan::where('personil_id', $personil->id)
            ->with('front')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($list);
    }

    public function show(Request $request, int $id)
    {
        $personil = $request->user();

        $permintaan = Permintaan::where('id', $id)
            ->where('personil_id', $personil->id)
            ->with('front')
            ->first();

        if (!$permintaan) {
            return response()->json(['message' => 'Permintaan tidak ditemukan'], 404);
        }

        return response()->json($permintaan);
    }

    public function downloadHasil(Request $request, int $id)
    {
        $personil = $request->user();

        $permintaan = Permintaan::where('id', $id)
            ->where('personil_id', $personil->id)
            ->first();

        if (!$permintaan || !$permintaan->hasil_pdf) {
            return response()->json(['message' => 'File belum tersedia'], 404);
        }

        $path = storage_path('app/private/' . $permintaan->hasil_pdf);

        if (!file_exists($path)) {
            return response()->json(['message' => 'File tidak ditemukan'], 404);
        }

        return response()->download($path, 'hasil_permintaan_' . $permintaan->id . '.pdf');
    }
}
