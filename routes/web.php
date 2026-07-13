<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\OutputController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Upload
Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');
Route::get('/upload/{upload}', [UploadController::class, 'show'])->name('upload.show');
Route::post('/upload/{upload}/rollback', [UploadController::class, 'rollback'])->name('upload.rollback');

// API: Upload progress (for AJAX polling)
Route::get('/api/upload/{upload}/progress', [UploadController::class, 'progress'])->name('api.upload.progress');

// History
Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
Route::get('/history/{upload}', [HistoryController::class, 'show'])->name('history.show');
Route::get('/history/{upload}/error-report', [HistoryController::class, 'downloadErrorReport'])->name('history.error-report');

// Output
Route::get('/output', [OutputController::class, 'index'])->name('output.index');
Route::get('/output/{upload}/download/{type}', [OutputController::class, 'download'])->name('output.download');
