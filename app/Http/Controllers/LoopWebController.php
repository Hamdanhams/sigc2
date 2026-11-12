<?php

namespace App\Http\Controllers;

use App\Models\LoopSampel;
use Illuminate\Support\Facades\Storage;

/** Halaman web Loop (/loop). Pencocokan foto berjalan di browser; server hanya menyajikan halaman dan model. */
class LoopWebController extends Controller
{
    public function halaman()
    {
        return view('loop', ['modelVersi' => LoopSampel::MODEL_VERSI]);
    }

    /** Foto sampel referensi (hanya tampil saat pengguna menekan "Lihat foto referensi"). */
    public function foto(string $code)
    {
        $sampel = LoopSampel::where('code', $code)->first();
        $disk = Storage::disk('public');

        if (!$sampel || !$sampel->foto || !$disk->exists($sampel->foto)) {
            abort(404);
        }

        return response()->file($disk->path($sampel->foto), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Model ONNX (sama dengan yang dibundel di aplikasi). Versi ada di URL, jadi boleh di-cache
     * browser selamanya; ganti model = ganti MODEL_VERSI = URL baru.
     */
    public function model(string $versi)
    {
        $path = config('loop.model');

        if ($versi !== LoopSampel::MODEL_VERSI || !is_file($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
