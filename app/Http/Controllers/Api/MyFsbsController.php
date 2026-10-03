<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fsbs;
use App\Services\BlockModelLookupService;
use App\Services\KodeSampelParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Status & revisi FSBS untuk Personil, per grup Front + Tanggal (WITA).
 */
class MyFsbsController extends Controller
{
    // created_at disimpan UTC; tanggal lapangan memakai WITA (UTC+8).
    private const TANGGAL_SQL = 'DATE(DATE_ADD(fsbs.created_at, INTERVAL 8 HOUR))';

    private const RIWAYAT_HARI = 30;

    /**
     * Daftar grup milik Personil yang login.
     */
    public function index(Request $request)
    {
        $groups = Fsbs::where('personil_id', $request->user()->id)
            ->where(function ($q) {
                $q->where('status_approval', 'ditolak')
                    ->orWhere('fsbs.created_at', '>=', now()->subDays(self::RIWAYAT_HARI));
            })
            ->selectRaw(
                'front, ' . self::TANGGAL_SQL . ' as tanggal, status_approval, '
                . 'COUNT(*) as jumlah, MAX(catatan_penolakan) as catatan_penolakan, '
                . 'MAX(ditolak_oleh_jabatan) as ditolak_oleh_jabatan'
            )
            ->groupByRaw('front, ' . self::TANGGAL_SQL . ', status_approval')
            ->orderByDesc('tanggal')
            ->orderBy('front')
            ->get();

        return response()->json($groups);
    }

    /**
     * Plot di dalam 1 grup (untuk halaman revisi).
     */
    public function plots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'front' => 'required|string',
            'tanggal' => 'required|date_format:Y-m-d',
            'status_approval' => 'required|in:menunggu,menunggu_wuh,disetujui,ditolak',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $plots = $this->group($request->user()->id, $request->front, $request->tanggal)
            ->where('status_approval', $request->status_approval)
            ->orderBy('kode_sampel')
            ->get();

        return response()->json($plots);
    }

    /**
     * Revisi grup yang DITOLAK: perbarui plot di tempat (created_at tetap, jadi
     * grup tidak berpindah tanggal), lalu kirim ulang dari lapis 1.
     */
    public function revise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'front' => 'required|string',
            'tanggal' => 'required|date_format:Y-m-d',
            'user_pegawai_id' => 'required|exists:user_pegawais,id',
            'plots' => 'required|array|min:1',
            'plots.*.id' => 'required|integer',
            'plots.*.kode_sampel' => 'required|string',
            'plots.*.koordinat_x' => 'nullable|numeric',
            'plots.*.koordinat_y' => 'nullable|numeric',
            'plots.*.keterangan' => 'nullable|string',
            'plots.*.foto_material' => 'nullable|string',
            'plots.*.increment' => 'nullable|string',
            'plots.*.gridding' => 'nullable|string',
            'hapus_ids' => 'nullable|array',
            'hapus_ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $personilId = $request->user()->id;

        $rows = $this->group($personilId, $request->front, $request->tanggal)
            ->where('status_approval', 'ditolak')
            ->get()
            ->keyBy('id');

        if ($rows->isEmpty()) {
            return response()->json(['message' => 'Tidak ada plot ditolak di grup ini'], 404);
        }

        $payload = collect($request->plots)->keyBy('id');
        if ($payload->keys()->diff($rows->keys())->isNotEmpty()) {
            return response()->json(['message' => 'Ada plot yang bukan bagian dari grup ditolak ini'], 422);
        }

        $hapusIds = collect($request->hapus_ids ?? [])->intersect($rows->keys());

        DB::transaction(function () use ($rows, $payload, $hapusIds, $request) {
            Fsbs::whereIn('id', $hapusIds)->delete();

            foreach ($payload as $id => $item) {
                if ($hapusIds->contains($id)) {
                    continue;
                }

                $kode = strtoupper($item['kode_sampel']);
                $hasil = app(KodeSampelParserService::class)->parse($kode);

                $lookup = null;
                if ($hasil['front'] && $hasil['titik_produksi'] && $hasil['elevasi']) {
                    $lookup = app(BlockModelLookupService::class)->lookup(
                        $hasil['front'],
                        $hasil['titik_produksi'],
                        $hasil['elevasi']
                    );
                }

                $rows[$id]->update([
                    'kode_sampel' => $kode,
                    'front' => $hasil['front'],
                    'titik_produksi' => $hasil['titik_produksi'],
                    'elevasi' => $hasil['elevasi'],
                    'huruf_running' => $hasil['huruf_running'],
                    'koordinat_x' => $item['koordinat_x'] ?? null,
                    'koordinat_y' => $item['koordinat_y'] ?? null,
                    'foto_material' => $item['foto_material'] ?? $rows[$id]->foto_material,
                    'keterangan' => strtoupper($item['keterangan'] ?? ''),
                    'increment' => strtoupper($item['increment'] ?? ''),
                    'gridding' => strtoupper($item['gridding'] ?? ''),
                    'ni_bm' => $lookup['ni_bm'] ?? null,
                    'fe_bm' => $lookup['fe_bm'] ?? null,
                ]);
            }

            // Plot ditolak yang tidak ikut dikirim tetap ikut kembali ke lapis 1 agar grup tidak terpecah.
            Fsbs::whereIn('id', $rows->keys()->diff($hapusIds))->update([
                'user_pegawai_id' => $request->user_pegawai_id,
                'status_approval' => 'menunggu',
                'catatan_penolakan' => null,
                'ditolak_oleh_jabatan' => null,
            ]);
        });

        return response()->json(['message' => 'Revisi berhasil dikirim ulang']);
    }

    private function group(int $personilId, string $front, string $tanggal)
    {
        return Fsbs::where('personil_id', $personilId)
            ->where('front', $front)
            ->whereRaw(self::TANGGAL_SQL . ' = ?', [$tanggal]);
    }
}
