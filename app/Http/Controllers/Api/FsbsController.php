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
            'client_uuid' => 'nullable|uuid',
            'dibuat_pada' => 'nullable|date',
            'ni' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $kodeSampel = strtoupper($data['kode_sampel']);

        // Kiriman ulang dari draft yang sama (respons sebelumnya hilang): jangan buat data ganda.
        if (!empty($data['client_uuid'])) {
            $sudahAda = Fsbs::where('client_uuid', $data['client_uuid'])->first();
            if ($sudahAda) {
                return response()->json(['message' => 'Data FSBS sudah tersimpan', 'data' => $sudahAda], 201);
            }
        }

        $hasil = app(KodeSampelParserService::class)->parse($kodeSampel);

        $lookup = null;
        if ($hasil['front'] && $hasil['titik_produksi'] && $hasil['elevasi']) {
            $lookup = app(BlockModelLookupService::class)->lookup(
                $hasil['front'],
                $hasil['titik_produksi'],
                $hasil['elevasi']
            );
        }

        $fsbs = new Fsbs([
            'kode_sampel' => $kodeSampel,
            'client_uuid' => $data['client_uuid'] ?? null,
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
            'ni' => $data['ni'] ?? null,
        ]);

        // Tanggal data = waktu plot di HP (bukan waktu sinkron). created_at dipakai untuk
        // pengelompokan approval per tanggal (WITA). Waktu yang tidak masuk akal diabaikan.
        if (!empty($data['dibuat_pada'])) {
            $waktuPlot = \Carbon\Carbon::parse($data['dibuat_pada']);
            if ($waktuPlot->lte(now()->addMinutes(10)) && $waktuPlot->gte(now()->subDays(90))) {
                $fsbs->created_at = $waktuPlot;
            }
        }

        $fsbs->save();

        return response()->json([
            'message' => 'Data FSBS berhasil disimpan',
            'data' => $fsbs,
        ], 201);
    }

    /**
     * Isi/ubah hasil analisa Ni sebuah plot. Boleh dilakukan siapa saja yang login
     * (keputusan user). Plot dikenali lewat client_uuid (draft HP) atau id server.
     * ni = null menghapus nilai.
     */
    public function updateNi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_uuid' => 'nullable|uuid|required_without:id',
            'id' => 'nullable|integer|required_without:client_uuid',
            'ni' => 'present|nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $fsbs = !empty($data['client_uuid'])
            ? Fsbs::where('client_uuid', $data['client_uuid'])->first()
            : Fsbs::find($data['id']);

        if (!$fsbs) {
            return response()->json(['message' => 'Plot belum ada di server (sinkronkan dulu)'], 404);
        }

        $fsbs->ni = $data['ni'];
        $fsbs->save();

        return response()->json(['message' => 'Hasil Ni tersimpan', 'data' => [
            'id' => $fsbs->id,
            'client_uuid' => $fsbs->client_uuid,
            'ni' => $fsbs->ni !== null ? (float) $fsbs->ni : null,
        ]]);
    }

    /**
     * Plot FSBS satu Front untuk ditampilkan di peta (termasuk plot HP lain).
     * front = inisial 3 huruf; hari = berapa hari ke belakang (default 90, maks 365).
     */
    public function peta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'front' => 'required|string|max:10',
            'hari' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $hari = (int) ($request->input('hari') ?: 90);

        $rows = Fsbs::where('front', strtoupper($request->input('front')))
            ->whereNotNull('koordinat_x')
            ->whereNotNull('koordinat_y')
            ->where('created_at', '>=', now()->subDays($hari))
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get(['id', 'client_uuid', 'kode_sampel', 'koordinat_x', 'koordinat_y', 'ni', 'created_at']);

        return response()->json($rows->map(fn($r) => [
            'id' => $r->id,
            'client_uuid' => $r->client_uuid,
            'kode_sampel' => $r->kode_sampel,
            'koordinat_x' => (float) $r->koordinat_x,
            'koordinat_y' => (float) $r->koordinat_y,
            'ni' => $r->ni !== null ? (float) $r->ni : null,
            'created_at' => $r->created_at,
        ])->values());
    }

    public function index(Request $request)
    {
        $fsbs = Fsbs::orderByDesc('created_at')->paginate(20);
        return response()->json($fsbs);
    }
}
