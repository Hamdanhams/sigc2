<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoopSampel;
use Illuminate\Http\Request;

/** Data sampel referensi Loop. PUBLIK (tanpa login): fitur Loop juga bisa dipakai dari layar login. */
class LoopController extends Controller
{
    /**
     * Seluruh sampel siap beserta sidik jari fotonya. Aplikasi mengirim ?versi=... dari unduhan
     * sebelumnya; bila data tidak berubah, server hanya membalas {tidak_berubah: true}.
     */
    public function sampel(Request $request)
    {
        $siap = LoopSampel::where('status', 'siap')->whereNotNull('embedding');

        $versi = md5(implode('|', [
            LoopSampel::MODEL_VERSI,
            (clone $siap)->count(),
            (clone $siap)->max('updated_at'),
        ]));

        if ($request->query('versi') === $versi) {
            return response()->json(['versi' => $versi, 'tidak_berubah' => true]);
        }

        $data = $siap->orderBy('code')->get()->map(fn(LoopSampel $s) => [
            'id' => $s->id,
            'code' => $s->code,
            'kode_sample' => $s->kode_sample,
            'inisial_front' => $s->inisial_front,
            'zona' => $s->zona,
            'warna' => $s->warna,
            'ukuran' => $s->ukuran,
            'komposisi' => $s->komposisi,
            'keterangan' => $s->keterangan,
            'kadar' => collect(LoopSampel::KADAR)
                ->mapWithKeys(fn($k) => [$k => $s->{$k} !== null ? (float) $s->{$k} : null]),
            'embedding' => $s->embedding,
        ]);

        return response()->json([
            'versi' => $versi,
            'model_versi' => LoopSampel::MODEL_VERSI,
            'sampel' => $data,
        ]);
    }
}
