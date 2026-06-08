<?php

use App\Http\Controllers\App\ActivityController;
use App\Http\Controllers\App\AttachmentController;
use App\Http\Controllers\App\KnowledgeBaseController;
use App\Http\Controllers\App\SettingsController;
use App\Http\Controllers\App\TicketController;
use App\Http\Controllers\App\TwoFactorController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Tenant\CompanySelectionController;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    $setupCompleted = Schema::hasTable('system_settings')
        && SystemSetting::get('setup.completed', false) === true;

    return view('welcome', [
        'setupCompleted' => $setupCompleted,
    ]);
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
    Route::get('/password/forgot', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/password/forgot', [PasswordResetController::class, 'store'])
        ->middleware('throttle:login')
        ->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'update'])
        ->middleware('throttle:login')
        ->name('password.update');
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.login');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
        ->middleware('throttle:login')
        ->name('two-factor.login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/app/companies', [CompanySelectionController::class, 'index'])->name('app.companies');
    Route::post('/app/companies/select', [CompanySelectionController::class, 'store'])->name('app.companies.select');

    Route::middleware('tenant')->group(function (): void {
        Route::redirect('/app/dashboard', '/app/tickets')->name('app.dashboard');
        Route::get('/app/activity', ActivityController::class)->name('app.activity.index');
        Route::get('/app/kb', [KnowledgeBaseController::class, 'index'])->name('app.kb.index');
        Route::get('/app/kb/create', [KnowledgeBaseController::class, 'create'])->name('app.kb.create');
        Route::post('/app/kb', [KnowledgeBaseController::class, 'store'])->name('app.kb.store');
        Route::get('/app/kb/{article}/edit', [KnowledgeBaseController::class, 'edit'])->name('app.kb.edit');
        Route::patch('/app/kb/{article}', [KnowledgeBaseController::class, 'update'])->name('app.kb.update');
        Route::patch('/app/kb/{article}/archive', [KnowledgeBaseController::class, 'archive'])->name('app.kb.archive');
        Route::delete('/app/kb/{article}', [KnowledgeBaseController::class, 'destroy'])->name('app.kb.destroy');
        Route::get('/app/kb/{article}', [KnowledgeBaseController::class, 'show'])->name('app.kb.show');
        Route::get('/app/tickets', [TicketController::class, 'index'])->name('app.tickets.index');
        Route::get('/app/tickets/create', [TicketController::class, 'create'])->name('app.tickets.create');
        Route::post('/app/tickets', [TicketController::class, 'store'])->name('app.tickets.store');
        Route::get('/app/tickets/{ticket}', [TicketController::class, 'show'])->name('app.tickets.show');
        Route::post('/app/tickets/{ticket}/assign-self', [TicketController::class, 'assignSelf'])->name('app.tickets.assign-self');
        Route::post('/app/tickets/{ticket}/messages', [TicketController::class, 'storeMessage'])->name('app.tickets.messages.store');
        Route::post('/app/tickets/{ticket}/replies', [TicketController::class, 'storeReply'])->name('app.tickets.replies.store');
        Route::post('/app/tickets/{ticket}/attachments', [TicketController::class, 'storeAttachment'])->name('app.tickets.attachments.store');
        Route::post('/app/tickets/{ticket}/merge', [TicketController::class, 'merge'])->name('app.tickets.merge.store');
        Route::patch('/app/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('app.tickets.status.update');
        Route::patch('/app/tickets/{ticket}/properties', [TicketController::class, 'updateProperties'])->name('app.tickets.properties.update');
        Route::get('/app/attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('app.attachments.download');
        Route::get('/app/settings', [SettingsController::class, 'index'])->name('app.settings');
        Route::post('/app/settings/two-factor/start', [TwoFactorController::class, 'start'])->name('app.settings.two-factor.start');
        Route::post('/app/settings/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('app.settings.two-factor.confirm');
        Route::delete('/app/settings/two-factor', [TwoFactorController::class, 'destroy'])->name('app.settings.two-factor.destroy');
        Route::post('/app/settings/mail', [SettingsController::class, 'storeMail'])->name('app.settings.mail.store');
        Route::post('/app/settings/mail/test', [SettingsController::class, 'testMail'])->name('app.settings.mail.test');
        Route::post('/app/settings/mail/oauth/{provider}/redirect', [SettingsController::class, 'redirectOAuth'])->name('app.settings.mail.oauth.redirect');
        Route::get('/app/settings/mail/oauth/{provider}/callback', [SettingsController::class, 'callbackOAuth'])->name('app.settings.mail.oauth.callback');
    });
});
