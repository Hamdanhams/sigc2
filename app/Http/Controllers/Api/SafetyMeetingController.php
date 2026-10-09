<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SafetyMeeting;
use App\Models\UserPegawai;
use App\Services\CloudinaryCleanupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Arsip dokumentasi Safety Meeting. Hanya akun User Pegawai (Pengawas, Pengawas
 * Senior, Work Unit Head); Personil tidak punya akses.
 *
 * Sengaja TIDAK ada endpoint hapus dan edit keterangan: keduanya hanya lewat
 * panel admin. Pembuat hanya boleh mengganti foto.
 */
class SafetyMeetingController extends Controller
{
    private function hanyaUserPegawai(Request $request)
    {
        if (!($request->user() instanceof UserPegawai)) {
            return response()->json(['message' => 'Fitur ini hanya untuk Pengawas dan Work Unit Head'], 403);
        }
        return null;
    }

    private function data(SafetyMeeting $sm, int $userId): array
    {
        return [
            'id' => $sm->id,
            'waktu' => $sm->waktu->toIso8601String(),
            'lokasi' => $sm->lokasi,
            'anggota' => $sm->anggota,
            'pembahasan' => $sm->pembahasan,
            'foto' => $sm->foto,
            'pembuat' => ['id' => $sm->user_pegawai_id, 'nama' => $sm->pembuat?->nama],
            'bisa_ganti_foto' => $sm->user_pegawai_id === $userId,
        ];
    }

    public function index(Request $request)
    {
        if ($tolak = $this->hanyaUserPegawai($request)) {
            return $tolak;
        }

        $userId = $request->user()->id;
        $page = SafetyMeeting::with('pembuat:id,nama')
            ->orderByDesc('waktu')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'data' => $page->getCollection()->map(fn($sm) => $this->data($sm, $userId))->values(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        if ($tolak = $this->hanyaUserPegawai($request)) {
            return $tolak;
        }

        $sm = SafetyMeeting::with('pembuat:id,nama')->findOrFail($id);
        return response()->json($this->data($sm, $request->user()->id));
    }

    public function store(Request $request)
    {
        if ($tolak = $this->hanyaUserPegawai($request)) {
            return $tolak;
        }

        $validator = Validator::make($request->all(), [
            'client_uuid' => 'required|uuid',
            'waktu' => 'required|date',
            'lokasi' => 'required|string|max:255',
            'anggota' => 'required|array|min:1',
            'anggota.*.id' => 'required|integer',
            'anggota.*.nama' => 'required|string|max:255',
            'pembahasan' => 'required|string',
            'foto' => 'required|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $userId = $request->user()->id;

        // Kiriman ulang dari antrean offline: kembalikan data yang sudah ada, jangan dobel.
        $ada = SafetyMeeting::with('pembuat:id,nama')->where('client_uuid', $request->client_uuid)->first();
        if ($ada) {
            return response()->json($this->data($ada, $userId));
        }

        $sm = SafetyMeeting::create([
            'client_uuid' => $request->client_uuid,
            'user_pegawai_id' => $userId,
            'waktu' => $request->waktu,
            'lokasi' => $request->lokasi,
            'anggota' => collect($request->anggota)->map(fn($a) => ['id' => $a['id'], 'nama' => $a['nama']])->all(),
            'pembahasan' => $request->pembahasan,
            'foto' => $request->foto,
        ])->load('pembuat:id,nama');

        return response()->json($this->data($sm, $userId), 201);
    }

    /**
     * Ganti foto saja. Hanya pembuat.
     */
    public function updateFoto(Request $request, int $id)
    {
        if ($tolak = $this->hanyaUserPegawai($request)) {
            return $tolak;
        }

        $validator = Validator::make($request->all(), [
            'foto' => 'required|url|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $sm = SafetyMeeting::findOrFail($id);
        $userId = $request->user()->id;

        if ($sm->user_pegawai_id !== $userId) {
            return response()->json(['message' => 'Hanya pembuat yang boleh mengganti foto'], 403);
        }

        $fotoLama = $sm->foto;
        $sm->update(['foto' => $request->foto]);

        // Foto lama dihapus dari Cloudinary agar tidak menumpuk.
        if ($fotoLama !== $request->foto) {
            app(CloudinaryCleanupService::class)->hapusDariUrl($fotoLama);
        }

        return response()->json($this->data($sm->load('pembuat:id,nama'), $userId));
    }
}
