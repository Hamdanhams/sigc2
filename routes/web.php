<?php

use App\Http\Controllers\LoopWebController;
use Illuminate\Support\Facades\Route;

// Halaman utama langsung ke panel admin.
Route::redirect('/', '/admin');

// Loop versi web (publik, tanpa login): scanner kemiripan material, jalan di browser.
Route::get('/loop', [LoopWebController::class, 'halaman'])->name('loop');
Route::get('/loop/model/{versi}.onnx', [LoopWebController::class, 'model'])
    ->where('versi', '[A-Za-z0-9._-]+');
