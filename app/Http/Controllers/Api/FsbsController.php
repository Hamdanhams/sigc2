<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fsbs;
use App\Services\KodeSampelParserService;
use App\Services\BlockModelLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FsbsController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_sampel' => 'required|string',
            'koordinat_x' => 'nullable|numeric',
            'koordinat_y' => 'nullable|numeric',
            'personil_id' => 'required|exists:personils,id',
            'user_pegawai_id' => 'nullable|exists:user_pegawais,id',
            'foto_material' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'increment' => 'nullable|string',
            'gridding' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $kodeSampel = strtoupper($data['kode_sampel']);

        $hasil = app(KodeSampelParserService::class)->parse($kodeSampel);

        $lookup = null;
        if ($hasil['front'] && $hasil['titik_produksi'] && $hasil['elevasi']) {
            $lookup = app(BlockModelLookupService::class)->lookup(
                $hasil['front'],
                $hasil['titik_produksi'],
                $hasil['elevasi']
            );
        }

        $fsbs = Fsbs::create([
            'kode_sampel' => $kodeSampel,
            'front' => $hasil['front'],
            'titik_produksi' => $hasil['titik_produksi'],
            'elevasi' => $hasil['elevasi'],
            'huruf_running' => $hasil['huruf_running'],
            'koordinat_x' => $data['koordinat_x'] ?? null,
            'koordinat_y' => $data['koordinat_y'] ?? null,
            'personil_id' => $data['personil_id'],
            'user_pegawai_id' => $data['user_pegawai_id'] ?? null,
            // Klien lama (tanpa Pilih Pengawas) tidak punya jalur approval -> langsung disetujui.
            'status_approval' => isset($data['user_pegawai_id']) ? 'menunggu' : 'disetujui',
            'foto_material' => $data['foto_material'] ?? null,
            'keterangan' => strtoupper($data['keterangan'] ?? ''),
            'increment' => strtoupper($data['increment'] ?? ''),
            'gridding' => strtoupper($data['gridding'] ?? ''),
            'ni_bm' => $lookup['ni_bm'] ?? null,
            'fe_bm' => $lookup['fe_bm'] ?? null,
        ]);

        return response()->json([
            'message' => 'Data FSBS berhasil disimpan',
            'data' => $fsbs,
        ], 201);
    }

    public function index(Request $request)
    {
        $fsbs = Fsbs::orderByDesc('created_at')->paginate(20);
        return response()->json($fsbs);
    }
}
