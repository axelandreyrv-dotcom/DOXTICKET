<?php

namespace Tests\Feature\Admin;

use App\Jobs\Admin\RunScheduledBackupJob;
use App\Models\BackupRun;
use App\Models\SystemSetting;
use App\Services\Admin\LocalBackupRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Mockery;
use Tests\TestCase;

class ScheduledBackupJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_backup_does_not_run_when_disabled(): void
    {
        SystemSetting::put('backups.schedule_enabled', false);
        SystemSetting::put('backups.schedule_hour', 2);
        Date::setTestNow('2026-06-06 02:15:00');

        $backupRunner = Mockery::mock(LocalBackupRunner::class);
        $backupRunner->shouldNotReceive('run');
        $this->app->instance(LocalBackupRunner::class, $backupRunner);

        $this->app->call([new RunScheduledBackupJob, 'handle']);

        $this->assertNull(SystemSetting::get('backups.last_scheduled_run_date'));
    }

    public function test_scheduled_backup_does_not_run_outside_configured_hour(): void
    {
        SystemSetting::put('backups.schedule_enabled', true);
        SystemSetting::put('backups.schedule_hour', 2);
        Date::setTestNow('2026-06-06 03:00:00');

        $backupRunner = Mockery::mock(LocalBackupRunner::class);
        $backupRunner->shouldNotReceive('run');
        $this->app->instance(LocalBackupRunner::class, $backupRunner);

        $this->app->call([new RunScheduledBackupJob, 'handle']);

        $this->assertNull(SystemSetting::get('backups.last_scheduled_run_date'));
    }

    public function test_scheduled_backup_runs_once_when_enabled_and_due(): void
    {
        SystemSetting::put('backups.schedule_enabled', true);
        SystemSetting::put('backups.schedule_hour', 2);
        Date::setTestNow('2026-06-06 02:00:00');
        $backupRun = BackupRun::query()->create([
            'status' => 'succeeded',
            'destination' => 'local',
            'started_at' => now(),
            'finished_at' => now(),
            'meta' => ['trigger' => 'scheduled'],
        ]);

        $backupRunner = Mockery::mock(LocalBackupRunner::class);
        $backupRunner->shouldReceive('run')->once()->with('scheduled')->andReturn($backupRun);
        $this->app->instance(LocalBackupRunner::class, $backupRunner);

        $this->app->call([new RunScheduledBackupJob, 'handle']);

        $this->assertSame('2026-06-06', SystemSetting::get('backups.last_scheduled_run_date'));
    }

    public function test_scheduled_backup_does_not_run_twice_on_same_day(): void
    {
        SystemSetting::put('backups.schedule_enabled', true);
        SystemSetting::put('backups.schedule_hour', 2);
        SystemSetting::put('backups.last_scheduled_run_date', '2026-06-06');
        Date::setTestNow('2026-06-06 02:30:00');

        $backupRunner = Mockery::mock(LocalBackupRunner::class);
        $backupRunner->shouldNotReceive('run');
        $this->app->instance(LocalBackupRunner::class, $backupRunner);

        $this->app->call([new RunScheduledBackupJob, 'handle']);

        $this->assertSame('2026-06-06', SystemSetting::get('backups.last_scheduled_run_date'));
    }
}
