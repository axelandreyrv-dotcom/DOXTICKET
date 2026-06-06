<?php

namespace App\Jobs\Admin;

use App\Services\Admin\BackupPolicySettings;
use App\Services\Admin\LocalBackupRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunScheduledBackupJob implements ShouldQueue
{
    use Queueable;

    public function handle(BackupPolicySettings $backupPolicy, LocalBackupRunner $backupRunner): void
    {
        if (! $backupPolicy->scheduleEnabled()) {
            return;
        }

        if ((int) now()->format('G') !== $backupPolicy->scheduleHour()) {
            return;
        }

        $today = now()->toDateString();

        if ($backupPolicy->lastScheduledRunDate() === $today) {
            return;
        }

        $backupRunner->run('scheduled');
        $backupPolicy->markScheduledRunDate($today);
    }
}
