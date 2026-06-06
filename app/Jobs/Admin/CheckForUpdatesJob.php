<?php

namespace App\Jobs\Admin;

use App\Services\Admin\GitHubReleaseUpdateChecker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckForUpdatesJob implements ShouldQueue
{
    use Queueable;

    public function handle(GitHubReleaseUpdateChecker $updateChecker): void
    {
        $updateChecker->check();
    }
}
