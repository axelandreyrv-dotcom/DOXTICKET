<?php

namespace App\Jobs\Admin;

use App\Services\Admin\LocalBackupPruner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunBackupRetentionPruneJob implements ShouldQueue
{
    use Queueable;

    public function handle(LocalBackupPruner $backupPruner): void
    {
        $backupPruner->prune();
    }
}
