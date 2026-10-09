<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cuti;
use App\Models\UserPegawai;
use App\Services\CutiException;
use App\Services\CutiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Approval cuti: Pengawas Senior (lapis 1) lalu Work Unit Head (lapis 2).
 * Pengawas biasa dan Personil tidak punya akses.
 */
class CutiApprovalController extends Controller
{
    public function __construct(private CutiService $service)
    {
    }

    private function bukanApprover(Request $request)
    {
        $user = $request->user();
        if (!($user instanceof UserPegawai) || !in_array($user->jabatan, ['pengawas_senior', 'work_unit_head'], true)) {
            return response()->json(['message' => 'Anda tidak berwenang untuk approval cuti'], 403);
        }
        return null;
    }

    private function format(Cuti $c, UserPegawai $user): array
    {
        return CutiController::format($c) + [
            'personil' => [
                'id' => $c->personil_id,
                'nama' => $c->personil?->nama,
                'saldo_cuti' => (int) $c->personil?->saldo_cuti,
            ],
            'menunggu_anda' => $this->service->bisaDiproses($c, $user),
        ];
    }

    public function index(Request $request)
    {
        if ($tolak = $this->bukanApprover($request)) {
            return $tolak;
        }

        $user = $request->user();
        $list = $this->service->queryUntukApprover($user)
            ->orderByDesc('id')
            ->get()
            ->map(fn($c) => $this->format($c, $user))
            ->values();

        return response()->json($list);
    }

    public function approve(Request $request, int $id)
    {
        if ($tolak = $this->bukanApprover($request)) {
            return $tolak;
        }

        try {
            $cuti = $this->service->setujui($id, $request->user());
        } catch (CutiException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $akhir = $cuti->status === 'disetujui';
        return response()->json([
            'message' => $akhir ? 'Cuti disetujui dan saldo dipotong' : 'Pengajuan diteruskan ke Work Unit Head',
            'data' => $this->format($cuti->load('personil:id,nama,saldo_cuti'), $request->user()),
        ]);
    }

    public function reject(Request $request, int $id)
    {
        if ($tolak = $this->bukanApprover($request)) {
            return $tolak;
        }

        $validator = Validator::make($request->all(), [
            'catatan_penolakan' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $cuti = $this->service->tolak($id, $request->user(), trim($request->catatan_penolakan));
        } catch (CutiException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Pengajuan cuti ditolak',
            'data' => $this->format($cuti->load('personil:id,nama,saldo_cuti'), $request->user()),
        ]);
    }
}
