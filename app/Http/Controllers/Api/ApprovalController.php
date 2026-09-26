<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApprovalController extends Controller
{
    /**
     * Daftar laporan yang menunggu approval untuk pengawas yang login.
     */
    public function index(Request $request)
    {
        $userPegawai = $request->user();

        $produksi = Produksi::where('user_pegawai_id', $userPegawai->id)
            ->with('details', 'front', 'pic1', 'pic2')
            ->orderByDesc('tanggal')
            ->get();

        return response()->json($produksi);
    }

    public function approve(Request $request, int $id)
    {
        $produksi = Produksi::findOrFail($id);

        if ($produksi->user_pegawai_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berwenang untuk laporan ini'], 403);
        }

        $produksi->update([
            'status_approval' => 'disetujui',
            'catatan_penolakan' => null,
        ]);

        return response()->json(['message' => 'Laporan berhasil disetujui', 'data' => $produksi]);
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

        if ($produksi->user_pegawai_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berwenang untuk laporan ini'], 403);
        }

        $produksi->update([
            'status_approval' => 'ditolak',
            'catatan_penolakan' => $request->catatan_penolakan,
        ]);

        return response()->json(['message' => 'Laporan ditolak', 'data' => $produksi]);
    }
}
