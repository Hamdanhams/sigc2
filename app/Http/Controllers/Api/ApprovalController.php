<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApprovalController extends Controller
{
    /**
     * Daftar laporan untuk user yang login.
     * - Pengawas: laporan yang ditujukan ke dirinya.
     * - Work Unit Head: kotak masuk bersama (laporan yang lolos Pengawas),
     *   plus yang disetujui / ditolak di lapis 2.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Produksi::with('details', 'front', 'pic1', 'pic2', 'userPegawai');

        if ($user->jabatan === 'work_unit_head') {
            $query->where(function ($q) {
                $q->whereIn('status_approval', ['menunggu_wuh', 'disetujui'])
                    ->orWhere(function ($q2) {
                        $q2->where('status_approval', 'ditolak')
                            ->where('ditolak_oleh_jabatan', 'work_unit_head');
                    });
            });
        } else {
            $query->where('user_pegawai_id', $user->id);
        }

        return response()->json($query->orderByDesc('tanggal')->get());
    }

    public function approve(Request $request, int $id)
    {
        $produksi = Produksi::findOrFail($id);
        $user = $request->user();

        if ($user->jabatan === 'work_unit_head') {
            if ($produksi->status_approval !== 'menunggu_wuh') {
                return response()->json(['message' => 'Laporan ini tidak sedang menunggu approval Work Unit Head'], 422);
            }
            $next = 'disetujui';
        } else {
            if ($produksi->user_pegawai_id !== $user->id) {
                return response()->json(['message' => 'Anda tidak berwenang untuk laporan ini'], 403);
            }
            if ($produksi->status_approval !== 'menunggu') {
                return response()->json(['message' => 'Laporan ini tidak sedang menunggu approval Pengawas'], 422);
            }
            $next = 'menunggu_wuh';
        }

        $produksi->update([
            'status_approval' => $next,
            'catatan_penolakan' => null,
            'ditolak_oleh_jabatan' => null,
        ]);

        return response()->json([
            'message' => $next === 'disetujui' ? 'Laporan berhasil disetujui' : 'Laporan diteruskan ke Work Unit Head',
            'data' => $produksi,
        ]);
    }

    public function reject(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'catatan_penolakan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $produksi = Produksi::findOrFail($id);
        $user = $request->user();

        if ($user->jabatan === 'work_unit_head') {
            if ($produksi->status_approval !== 'menunggu_wuh') {
                return response()->json(['message' => 'Laporan ini tidak sedang menunggu approval Work Unit Head'], 422);
            }
        } else {
            if ($produksi->user_pegawai_id !== $user->id) {
                return response()->json(['message' => 'Anda tidak berwenang untuk laporan ini'], 403);
            }
            if ($produksi->status_approval !== 'menunggu') {
                return response()->json(['message' => 'Laporan ini tidak sedang menunggu approval Pengawas'], 422);
            }
        }

        $produksi->update([
            'status_approval' => 'ditolak',
            'catatan_penolakan' => $request->catatan_penolakan,
            'ditolak_oleh_jabatan' => $user->jabatan,
        ]);

        return response()->json(['message' => 'Laporan ditolak', 'data' => $produksi]);
    }
}
