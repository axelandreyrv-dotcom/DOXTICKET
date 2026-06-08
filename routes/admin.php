<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\MembershipController;
use App\Http\Controllers\Admin\RollbackController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TelemetryController;
use App\Http\Controllers\Admin\UpdateCheckController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'superadmin'])->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/health', AdminDashboardController::class)->name('health');
    Route::get('/audit', AuditLogController::class)->name('audit.index');
    Route::get('/audit/export', [AuditLogController::class, 'export'])->name('audit.export');
    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->name('companies.destroy');
    Route::post('/companies/{company}/status', [CompanyController::class, 'updateStatus'])->name('companies.status');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/invite', [UserController::class, 'invite'])->name('users.invite');
    Route::post('/users/invite', [UserController::class, 'storeInvite'])->name('users.invite.store');
    Route::post('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
    Route::post('/users/{user}/password-reset', [UserController::class, 'sendPasswordReset'])->name('users.password-reset');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::put('/memberships/{membership}', [MembershipController::class, 'update'])->name('memberships.update');
    Route::delete('/memberships/{membership}', [MembershipController::class, 'destroy'])->name('memberships.destroy');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::post('/rollback', [RollbackController::class, 'store'])->name('rollback.store');
    Route::post('/telemetry', [TelemetryController::class, 'update'])->name('telemetry.update');
    Route::post('/updates/check', [UpdateCheckController::class, 'store'])->name('updates.check');
});
