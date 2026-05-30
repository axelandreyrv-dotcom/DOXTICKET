<?php

use App\Http\Controllers\Setup\SetupController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:setup')->group(function (): void {
    Route::get('/setup', [SetupController::class, 'create'])->name('setup');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
});
