<?php

use App\Http\Controllers\LoopWebController;
use Illuminate\Support\Facades\Route;

// Halaman utama langsung ke panel admin.
Route::redirect('/', '/admin');

// GeoLens (dulu "Loop"), versi web: publik, tanpa login. Pencocokan foto jalan di browser.
// Endpoint DATA tetap /api/loop/sampel (dipakai aplikasi Flutter; jangan diganti).
Route::get('/geolens', [LoopWebController::class, 'halaman'])->name('geolens');
Route::get('/geolens/foto/{code}', [LoopWebController::class, 'foto'])
    ->where('code', '[A-Za-z0-9._-]+')->middleware('throttle:60,1');
Route::get('/geolens/model/{versi}.onnx', [LoopWebController::class, 'model'])
    ->where('versi', '[A-Za-z0-9._-]+');

// Alamat lama: arahkan ke GeoLens (tautan yang sudah beredar tetap jalan).
Route::redirect('/loop', '/geolens', 301);
