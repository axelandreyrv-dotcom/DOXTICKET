<?php

use Illuminate\Support\Facades\Route;

Route::get('/setup', function () {
    return view('setup.placeholder');
})->name('setup');

