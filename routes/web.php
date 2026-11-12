<?php

use App\Http\Controllers\QueueController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('queue.index');
});

Route::prefix('antrian')->name('queue.')->group(function () {
    Route::get('/', [QueueController::class, 'index'])->name('index');
    Route::post('/manual', [QueueController::class, 'storeManual'])->name('store-manual');
    Route::post('/{queue}/call', [QueueController::class, 'call'])->name('call');
    Route::post('/{queue}/start', [QueueController::class, 'start'])->name('start');
    Route::post('/{queue}/finish', [QueueController::class, 'finish'])->name('finish');
    Route::post('/{queue}/skip', [QueueController::class, 'skip'])->name('skip');
    Route::post('/{queue}/cancel', [QueueController::class, 'cancel'])->name('cancel');
});

Route::get('/display/{clinic_slug}', [QueueController::class, 'display'])->name('display');
