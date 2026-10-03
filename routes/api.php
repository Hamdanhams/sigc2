<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\ProduksiController;
use App\Http\Controllers\Api\FsbsController;
use App\Http\Controllers\Api\PetaLayerController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\SebaranFsbsController;
use App\Http\Controllers\Api\PetaSebaranController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\FsbsApprovalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PermintaanController;

// ================= PUBLIC (tanpa login) =================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-pengawas', [AuthController::class, 'loginPengawas']);

// ================= PROTECTED (wajib login) =================
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return $request->user();
    });

    Route::post('/upload-foto', [UploadController::class, 'uploadFoto']);

    // Produksi
    Route::post('/produksi', [ProduksiController::class, 'store']);
    Route::get('/produksi', [ProduksiController::class, 'index']);
    Route::get('/produksi/{id}', [ProduksiController::class, 'show']);
    Route::put('/produksi/{id}', [ProduksiController::class, 'update']);
    Route::get('/my-produksi', [ProduksiController::class, 'myReports']);

    // FSBS
    Route::post('/fsbs', [FsbsController::class, 'store']);
    Route::get('/fsbs', [FsbsController::class, 'index']);

    // Peta Layer
    Route::get('/peta', [PetaLayerController::class, 'index']);
    Route::get('/peta/{id}/download', [PetaLayerController::class, 'download'])->name('api.peta.download');

    // Master Data
    Route::get('/master/fronts', [MasterDataController::class, 'fronts']);
    Route::get('/master/personils', [MasterDataController::class, 'personils']);
    Route::get('/master/user-pegawais', [MasterDataController::class, 'userPegawais']);

    // Sebaran FSBS + Peta Sebaran
    Route::get('/sebaran-fsbs/{front}', [SebaranFsbsController::class, 'byFront']);
    Route::get('/peta-sebaran', [PetaSebaranController::class, 'index']);
    Route::get('/peta-sebaran/{id}/download', [PetaSebaranController::class, 'download'])->name('api.peta-sebaran.download');

    // Approval Produksi
    Route::get('/approval/produksi', [ApprovalController::class, 'index']);
    Route::patch('/approval/produksi/{id}/approve', [ApprovalController::class, 'approve']);
    Route::patch('/approval/produksi/{id}/reject', [ApprovalController::class, 'reject']);

    // Approval FSBS (per grup Front + Tanggal)
    Route::get('/approval/fsbs', [FsbsApprovalController::class, 'index']);
    Route::get('/approval/fsbs/plots', [FsbsApprovalController::class, 'show']);
    Route::patch('/approval/fsbs/approve', [FsbsApprovalController::class, 'approve']);
    Route::patch('/approval/fsbs/reject', [FsbsApprovalController::class, 'reject']);

    Route::post('/permintaan', [PermintaanController::class, 'store']);
    Route::get('/permintaan', [PermintaanController::class, 'index']);
    Route::get('/permintaan/{id}', [PermintaanController::class, 'show']);
    Route::get('/permintaan/{id}/download-hasil', [PermintaanController::class, 'downloadHasil'])->name('api.permintaan.download');
});
