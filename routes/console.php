<?php

use App\Jobs\Mail\IngestMailboxJob;
use App\Models\MailAccount;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    MailAccount::withoutTenant()
        ->where('is_active', true)
        ->orderBy('id')
        ->pluck('id')
        ->each(fn (int $mailAccountId) => IngestMailboxJob::dispatch($mailAccountId));
})->everyMinute()->name('mailbox-ingestion');
