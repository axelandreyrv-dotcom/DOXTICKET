<?php

use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\TicketController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Tenant\CompanySelectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/app/companies', [CompanySelectionController::class, 'index'])->name('app.companies');
    Route::post('/app/companies/select', [CompanySelectionController::class, 'store'])->name('app.companies.select');

    Route::middleware('tenant')->group(function (): void {
        Route::get('/app/dashboard', DashboardController::class)->name('app.dashboard');
        Route::get('/app/tickets', [TicketController::class, 'index'])->name('app.tickets.index');
        Route::get('/app/tickets/create', [TicketController::class, 'create'])->name('app.tickets.create');
        Route::post('/app/tickets', [TicketController::class, 'store'])->name('app.tickets.store');
    });
});
