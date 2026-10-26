<?php

use Illuminate\Support\Facades\Route;

// Halaman utama langsung ke panel admin.
Route::redirect('/', '/admin');
