<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginSlide;

/**
 * Slide layar login (PUBLIK, dipanggil sebelum ada token).
 * Hanya mengembalikan foto + teks yang memang untuk ditampilkan.
 */
class LoginSlideController extends Controller
{
    public function index()
    {
        $slides = LoginSlide::where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->map(fn(LoginSlide $s) => [
                'id' => $s->id,
                'gambar' => $this->kecilkan($s->gambar),
                'judul' => $s->judul,
                'keterangan' => $s->keterangan,
            ]);

        return response()->json(['data' => $slides]);
    }

    /** Minta Cloudinary mengecilkan/mengompres foto (maks lebar 1080) lewat URL transformasi. */
    private function kecilkan(string $url): string
    {
        if (str_contains($url, 'res.cloudinary.com') && str_contains($url, '/upload/')) {
            return preg_replace('#/upload/#', '/upload/c_limit,w_1080,q_auto/', $url, 1);
        }

        return $url;
    }
}
