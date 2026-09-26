<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produksi;
use App\Models\Front;
use App\Services\BlockModelLookupService;
use App\Services\RunningNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProduksiController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'shift' => 'required|in:1,2,3',
            'jam_mulai' => 'nullable',
            'jam_selesai' => 'nullable',
            'front_id' => 'required|exists:fronts,id',
            'fleet' => 'nullable',
            'pic_1_id' => 'required|exists:personils,id',
            'pic_2_id' => 'nullable|exists:personils,id',
            'user_pegawai_id' => 'nullable|exists:user_pegawais,id',
            'dokumentasi_produksi' => 'nullable|array',
            'dokumentasi_kendala' => 'nullable|array',
            'keterangan' => 'nullable|string',
            'rencana_produksi_besok' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.titik_produksi' => 'required|string',
            'details.*.elevasi_atas' => 'nullable|numeric',
            'details.*.prediksi_kadar' => 'nullable|in:H,K',
            'details.*.tujuan_dumping' => 'nullable|string',
            'details.*.ni_bm' => 'nullable|numeric',
            'details.*.fe_bm' => 'nullable|numeric',
            'details.*.ritase' => 'nullable|numeric',
            'details.*.gridding' => 'nullable|string',
            'details.*.running_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $front = Front::find($data['front_id']);
        $tahun = (int) date('Y', strtotime($data['tanggal']));

        $produksi = DB::transaction(function () use ($data, $front, $tahun) {
            $produksi = Produksi::create([
                'tanggal' => $data['tanggal'],
                'shift' => $data['shift'],
                'jam_mulai' => $data['jam_mulai'] ?? null,
                'jam_selesai' => $data['jam_selesai'] ?? null,
                'front_id' => $data['front_id'],
                'fleet' => strtoupper($data['fleet'] ?? ''),
                'pic_1_id' => $data['pic_1_id'],
                'pic_2_id' => $data['pic_2_id'] ?? null,
                'user_pegawai_id' => $data['user_pegawai_id'] ?? null,
                'dokumentasi_produksi' => $data['dokumentasi_produksi'],
                'dokumentasi_kendala' => $data['dokumentasi_kendala'] ?? null,
                'keterangan' => strtoupper($data['keterangan'] ?? ''),
                'rencana_produksi_besok' => strtoupper($data['rencana_produksi_besok'] ?? ''),
            ]);

            $itemsSoFar = [];

            foreach ($data['details'] as $item) {
                $titik = strtoupper($item['titik_produksi']);
                $elevasi = $item['elevasi_atas'] ?? null;

                $lookup = null;
                if ($elevasi !== null) {
                    $lookup = app(BlockModelLookupService::class)->lookup(
                        $front->inisial,
                        $titik,
                        (string) $elevasi
                    );
                }

                $runningNumber = $item['running_number'] ?? null;

                if (empty($runningNumber) && !empty($item['prediksi_kadar'])) {
                    $runningNumber = app(RunningNumberService::class)->generate(
                        $front->id,
                        $item['prediksi_kadar'],
                        $tahun,
                        $itemsSoFar
                    );
                }

                $detail = $produksi->details()->create([
                    'titik_produksi' => $titik,
                    'elevasi_atas' => $elevasi,
                    'running_number' => $runningNumber,
                    'tujuan_dumping' => strtoupper($item['tujuan_dumping'] ?? ''),
                    'ni_bm' => $lookup['ni_bm'] ?? ($item['ni_bm'] ?? null),
                    'fe_bm' => $lookup['fe_bm'] ?? ($item['fe_bm'] ?? null),
                    'ritase' => $item['ritase'] ?? null,
                    'gridding' => strtoupper($item['gridding'] ?? ''),
                ]);

                $itemsSoFar[] = [
                    'prediksi_kadar' => $item['prediksi_kadar'] ?? null,
                    'running_number' => $runningNumber,
                ];
            }

            return $produksi;
        });

        return response()->json([
            'message' => 'Laporan produksi berhasil disimpan',
            'data' => $produksi->load('details'),
        ], 201);
    }

    public function index(Request $request)
    {
        $produksi = Produksi::with('details', 'front', 'pic1', 'pic2')
            ->orderByDesc('tanggal')
            ->paginate(20);

        return response()->json($produksi);
    }

    public function myReports(Request $request)
    {
        $personil = $request->user();

        $produksi = Produksi::where('pic_1_id', $personil->id)
            ->orWhere('pic_2_id', $personil->id)
            ->with('details', 'front')
            ->orderByDesc('tanggal')
            ->get();

        return response()->json($produksi);
    }
    public function show(Request $request, int $id)
    {
        $personil = $request->user();

        $produksi = Produksi::where('id', $id)
            ->where('pic_1_id', $personil->id)
            ->with('details')
            ->first();

        if (!$produksi) {
            return response()->json(['message' => 'Laporan tidak ditemukan'], 404);
        }

        return response()->json($produksi);
    }

    public function update(Request $request, int $id)
    {
        $personil = $request->user();

        $produksi = Produksi::where('id', $id)->where('pic_1_id', $personil->id)->first();

        if (!$produksi) {
            return response()->json(['message' => 'Laporan tidak ditemukan'], 404);
        }

        if ($produksi->status_approval !== 'ditolak') {
            return response()->json(['message' => 'Hanya laporan yang ditolak yang bisa direvisi'], 403);
        }

        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'shift' => 'required|in:1,2,3',
            'jam_mulai' => 'nullable',
            'jam_selesai' => 'nullable',
            'front_id' => 'required|exists:fronts,id',
            'fleet' => 'nullable',
            'pic_1_id' => 'required|exists:personils,id',
            'pic_2_id' => 'nullable|exists:personils,id',
            'user_pegawai_id' => 'nullable|exists:user_pegawais,id',
            'dokumentasi_produksi' => 'nullable|array',
            'dokumentasi_kendala' => 'nullable|array',
            'keterangan' => 'nullable|string',
            'rencana_produksi_besok' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.titik_produksi' => 'required|string',
            'details.*.elevasi_atas' => 'nullable|numeric',
            'details.*.running_number' => 'nullable|string',
            'details.*.tujuan_dumping' => 'nullable|string',
            'details.*.ni_bm' => 'nullable|numeric',
            'details.*.fe_bm' => 'nullable|numeric',
            'details.*.ritase' => 'nullable|numeric',
            'details.*.gridding' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $front = Front::find($data['front_id']);

        DB::transaction(function () use ($produksi, $data, $front) {
            $produksi->update([
                'tanggal' => $data['tanggal'],
                'shift' => $data['shift'],
                'jam_mulai' => $data['jam_mulai'] ?? null,
                'jam_selesai' => $data['jam_selesai'] ?? null,
                'front_id' => $data['front_id'],
                'fleet' => strtoupper($data['fleet'] ?? ''),
                'pic_1_id' => $data['pic_1_id'],
                'pic_2_id' => $data['pic_2_id'] ?? null,
                'user_pegawai_id' => $data['user_pegawai_id'] ?? null,
                'dokumentasi_produksi' => $data['dokumentasi_produksi'] ?? [],
                'dokumentasi_kendala' => $data['dokumentasi_kendala'] ?? null,
                'keterangan' => strtoupper($data['keterangan'] ?? ''),
                'rencana_produksi_besok' => strtoupper($data['rencana_produksi_besok'] ?? ''),
                'status_approval' => 'menunggu',
                'catatan_penolakan' => null,
            ]);

            $produksi->details()->delete();

            foreach ($data['details'] as $item) {
                $titik = strtoupper($item['titik_produksi']);
                $elevasi = $item['elevasi_atas'] ?? null;

                $lookup = null;
                if ($elevasi !== null) {
                    $lookup = app(BlockModelLookupService::class)->lookup(
                        $front->inisial,
                        $titik,
                        (string) $elevasi
                    );
                }

                $produksi->details()->create([
                    'titik_produksi' => $titik,
                    'elevasi_atas' => $elevasi,
                    'running_number' => $item['running_number'] ?? null,
                    'tujuan_dumping' => strtoupper($item['tujuan_dumping'] ?? ''),
                    'ni_bm' => $lookup['ni_bm'] ?? ($item['ni_bm'] ?? null),
                    'fe_bm' => $lookup['fe_bm'] ?? ($item['fe_bm'] ?? null),
                    'ritase' => $item['ritase'] ?? null,
                    'gridding' => strtoupper($item['gridding'] ?? ''),
                ]);
            }
        });

        return response()->json([
            'message' => 'Laporan berhasil direvisi dan dikirim ulang',
            'data' => $produksi->fresh('details'),
        ]);
    }
}
