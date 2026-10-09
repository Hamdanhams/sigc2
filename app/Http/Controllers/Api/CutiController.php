<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cuti;
use App\Models\Personil;
use App\Services\CutiException;
use App\Services\CutiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Pengajuan cuti dari sisi Personil. Aturan ada di CutiService.
 * Tidak ada endpoint edit/batal: ditolak = buat pengajuan baru.
 */
class CutiController extends Controller
{
    public function __construct(private CutiService $service)
    {
    }

    private function hanyaPersonil(Request $request)
    {
        if (!($request->user() instanceof Personil)) {
            return response()->json(['message' => 'Fitur ini hanya untuk Personil'], 403);
        }
        return null;
    }

    public static function format(Cuti $c): array
    {
        return [
            'id' => $c->id,
            'tanggal_mulai' => $c->tanggal_mulai->toDateString(),
            'tanggal_selesai' => $c->tanggal_selesai->toDateString(),
            'jumlah_hari' => $c->jumlah_hari,
            'alasan' => $c->alasan,
            'status' => $c->status,
            'catatan_penolakan' => $c->catatan_penolakan,
            'ditolak_oleh_jabatan' => $c->ditolak_oleh_jabatan,
            'diajukan_pada' => $c->created_at?->toIso8601String(),
        ];
    }

    /** Saldo + riwayat pengajuan milik Personil yang login. */
    public function index(Request $request)
    {
        if ($tolak = $this->hanyaPersonil($request)) {
            return $tolak;
        }

        $personil = $request->user()->fresh();

        return response()->json([
            'ringkasan' => $this->service->ringkasan($personil),
            'pengajuan' => Cuti::where('personil_id', $personil->id)
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(fn($c) => self::format($c))
                ->values(),
        ]);
    }

    /** Pratinjau hitungan hari & saldo untuk formulir (tidak menyimpan). */
    public function hitung(Request $request)
    {
        if ($tolak = $this->hanyaPersonil($request)) {
            return $tolak;
        }

        $validator = Validator::make($request->all(), [
            'tanggal_mulai' => 'required|date_format:Y-m-d',
            'tanggal_selesai' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $mulai = $this->service->parseTanggal($request->tanggal_mulai);
        $selesai = $this->service->parseTanggal($request->tanggal_selesai);
        if ($selesai->lt($mulai)) {
            return response()->json(['message' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.'], 422);
        }

        return response()->json($this->service->pratinjau($request->user()->fresh(), $mulai, $selesai));
    }

    public function store(Request $request)
    {
        if ($tolak = $this->hanyaPersonil($request)) {
            return $tolak;
        }

        $validator = Validator::make($request->all(), [
            'tanggal_mulai' => 'required|date_format:Y-m-d',
            'tanggal_selesai' => 'required|date_format:Y-m-d',
            'alasan' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        try {
            $cuti = $this->service->ajukan(
                $request->user(),
                $this->service->parseTanggal($request->tanggal_mulai),
                $this->service->parseTanggal($request->tanggal_selesai),
                trim($request->alasan)
            );
        } catch (CutiException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(self::format($cuti), 201);
    }

    public function show(Request $request, int $id)
    {
        if ($tolak = $this->hanyaPersonil($request)) {
            return $tolak;
        }

        $cuti = Cuti::where('personil_id', $request->user()->id)->findOrFail($id);
        return response()->json(self::format($cuti));
    }
}
