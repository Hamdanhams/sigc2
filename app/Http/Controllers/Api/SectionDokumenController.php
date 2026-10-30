<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SectionDokumen;
use Illuminate\Support\Facades\Storage;

class SectionDokumenController extends Controller
{
    /** Semua dokumen Section (aplikasi menyaring per Front). */
    public function index()
    {
        $daftar = SectionDokumen::orderBy('front_id')
            ->orderBy('judul')
            ->get()
            ->map(fn(SectionDokumen $d) => [
                'id' => $d->id,
                'front_id' => $d->front_id,
                'judul' => $d->judul,
                'ukuran' => $d->ukuran,
                'download_url' => route('api.section.download', $d->id),
                'updated_at' => $d->updated_at,
            ]);

        return response()->json($daftar);
    }

    public function download(int $id)
    {
        $dokumen = SectionDokumen::findOrFail($id);
        $disk = Storage::disk('local');

        if (!$dokumen->file_pdf || !$disk->exists($dokumen->file_pdf)) {
            return response()->json(['message' => 'File tidak ditemukan'], 404);
        }

        return response()->file($disk->path($dokumen->file_pdf), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="section-' . $dokumen->id . '.pdf"',
        ]);
    }
}
