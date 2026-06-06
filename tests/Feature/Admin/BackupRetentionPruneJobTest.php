<?php

namespace Tests\Feature\Admin;

use App\Jobs\Admin\RunBackupRetentionPruneJob;
use App\Services\Admin\LocalBackupPruner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BackupRetentionPruneJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_runs_local_backup_pruner(): void
    {
        $pruner = Mockery::mock(LocalBackupPruner::class);
        $pruner->shouldReceive('prune')->once()->andReturn(['pruned' => 2]);
        $this->app->instance(LocalBackupPruner::class, $pruner);

        $this->app->call([new RunBackupRetentionPruneJob, 'handle']);
    }
}
