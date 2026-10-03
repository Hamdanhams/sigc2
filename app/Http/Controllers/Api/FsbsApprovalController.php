<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fsbs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Approval FSBS 2 lapis (Pengawas -> Work Unit Head).
 * Approval berlaku per GRUP: Front + tanggal (created_at, zona WITA) + status.
 */
class FsbsApprovalController extends Controller
{
    // created_at disimpan UTC; tanggal lapangan memakai WITA (UTC+8).
    private const TANGGAL_SQL = 'DATE(DATE_ADD(fsbs.created_at, INTERVAL 8 HOUR))';

    // Riwayat (non-pending) hanya ditampilkan N hari terakhir agar daftar tidak membengkak.
    private const RIWAYAT_HARI = 30;

    private function isWuh($user): bool
    {
        return $user->jabatan === 'work_unit_head';
    }

    private function pendingStatus($user): string
    {
        return $this->isWuh($user) ? 'menunggu_wuh' : 'menunggu';
    }

    /** Batasi record sesuai wewenang user yang login. */
    private function scoped($user)
    {
        $query = Fsbs::query();

        if ($this->isWuh($user)) {
            return $query->where(function ($q) {
                $q->whereIn('status_approval', ['menunggu_wuh', 'disetujui'])
                    ->orWhere(function ($q2) {
                        $q2->where('status_approval', 'ditolak')
                            ->where('ditolak_oleh_jabatan', 'work_unit_head');
                    });
            });
        }

        return $query->where('user_pegawai_id', $user->id);
    }

    /**
     * Daftar grup (Front + Tanggal + Status).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $pending = $this->pendingStatus($user);

        $groups = $this->scoped($user)
            ->where(function ($q) use ($pending) {
                $q->where('status_approval', $pending)
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
     * Daftar plot (kode sampel) di dalam 1 grup.
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'front' => 'required|string',
            'tanggal' => 'required|date_format:Y-m-d',
            'status_approval' => 'required|in:menunggu,menunggu_wuh,disetujui,ditolak',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $plots = $this->scoped($request->user())
            ->where('front', $request->front)
            ->whereRaw(self::TANGGAL_SQL . ' = ?', [$request->tanggal])
            ->where('status_approval', $request->status_approval)
            ->with('personil:id,nama')
            ->orderBy('kode_sampel')
            ->get();

        return response()->json($plots);
    }

    public function approve(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'front' => 'required|string',
            'tanggal' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = $request->user();
        $next = $this->isWuh($user) ? 'disetujui' : 'menunggu_wuh';

        $count = $this->targetGroup($user, $request)->update([
            'status_approval' => $next,
            'catatan_penolakan' => null,
            'ditolak_oleh_jabatan' => null,
        ]);

        if ($count === 0) {
            return response()->json(['message' => 'Tidak ada plot yang menunggu approval Anda di grup ini'], 422);
        }

        return response()->json([
            'message' => $next === 'disetujui'
                ? "$count plot berhasil disetujui"
                : "$count plot diteruskan ke Work Unit Head",
            'jumlah' => $count,
        ]);
    }

    public function reject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'front' => 'required|string',
            'tanggal' => 'required|date_format:Y-m-d',
            'catatan_penolakan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = $request->user();

        $count = $this->targetGroup($user, $request)->update([
            'status_approval' => 'ditolak',
            'catatan_penolakan' => $request->catatan_penolakan,
            'ditolak_oleh_jabatan' => $user->jabatan,
        ]);

        if ($count === 0) {
            return response()->json(['message' => 'Tidak ada plot yang menunggu approval Anda di grup ini'], 422);
        }

        return response()->json(['message' => "$count plot ditolak", 'jumlah' => $count]);
    }

    /** Plot dalam grup yang sedang menunggu aksi user ini. */
    private function targetGroup($user, Request $request)
    {
        return $this->scoped($user)
            ->where('front', $request->front)
            ->whereRaw(self::TANGGAL_SQL . ' = ?', [$request->tanggal])
            ->where('status_approval', $this->pendingStatus($user));
    }
}
