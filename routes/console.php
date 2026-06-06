<?php

use App\Jobs\Admin\CheckForUpdatesJob;
use App\Jobs\Admin\RecordQueueHeartbeatJob;
use App\Jobs\Admin\RunBackupRetentionPruneJob;
use App\Jobs\Admin\RunScheduledBackupJob;
use App\Jobs\Mail\IngestMailboxJob;
use App\Jobs\Mail\RefreshOAuthMailAccountsJob;
use App\Jobs\Tickets\ScheduleSlaCheckJob;
use App\Models\MailAccount;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Cache::put('doxticket:health:scheduler:last_run', now()->toISOString(), now()->addHour());
    RecordQueueHeartbeatJob::dispatch();
})->everyMinute()->name('health-heartbeats');

Schedule::call(function (): void {
    MailAccount::withoutTenant()
        ->where('is_active', true)
        ->orderBy('id')
        ->pluck('id')
        ->each(fn (int $mailAccountId) => IngestMailboxJob::dispatch($mailAccountId));
})->everyMinute()->name('mailbox-ingestion');

Schedule::job(new RefreshOAuthMailAccountsJob, 'mail')
    ->everyFiveMinutes()
    ->name('oauth-mail-token-refresh');

Schedule::job(new CheckForUpdatesJob)
    ->daily()
    ->name('github-release-update-check');

Schedule::job(new RunScheduledBackupJob)
    ->hourly()
    ->name('scheduled-local-backup');

Schedule::job(new RunBackupRetentionPruneJob)
    ->dailyAt('03:30')
    ->name('local-backup-retention-prune');

Schedule::job(new ScheduleSlaCheckJob)
    ->everyFiveMinutes()
    ->name('ticket-sla-check');
