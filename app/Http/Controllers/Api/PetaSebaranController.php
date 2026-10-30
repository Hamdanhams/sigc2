<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * Peta Sebaran sudah dihapus (digantikan Peta Block Model). Controller ini tinggal
 * penjaga untuk APK lama supaya tidak error: daftar kosong, unduhan 404.
 * Hapus bersama route /peta-sebaran bila semua pengguna sudah memakai APK baru.
 */
class PetaSebaranController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }

    public function download(int $id)
    {
        return response()->json(['message' => 'Peta Sebaran sudah tidak tersedia'], 404);
    }
}
