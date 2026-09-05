<?php

use App\Http\Controllers\PengaduanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PengaduanController::class, 'index'])->name('pengaduan.index');

// Batasi 5 pengiriman per menit per IP untuk mencegah spam ke SOAP backend
Route::post('/pengaduan', [PengaduanController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('pengaduan.store');

// Endpoint AJAX internal — 60 req/menit umum, cek lebih ketat (20/menit)
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/kecamatan',     [PengaduanController::class, 'kecamatan'])->middleware('throttle:60,1')->name('kecamatan');
    Route::get('/desa',          [PengaduanController::class, 'desa'])->middleware('throttle:60,1')->name('desa');
    Route::get('/cek-pengaduan', [PengaduanController::class, 'cek'])->middleware('throttle:20,1')->name('cek');
});
