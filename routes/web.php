<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.placeholder');
})->name('login');

Route::get('/app/dashboard', function () {
    return view('app.placeholder');
})->name('app.dashboard');
