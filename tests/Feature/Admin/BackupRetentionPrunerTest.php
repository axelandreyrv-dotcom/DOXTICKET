<?php

namespace Tests\Feature\Admin;

use App\Models\BackupRun;
use App\Models\SystemSetting;
use App\Services\Admin\LocalBackupPruner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupRetentionPrunerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Date::setTestNow();

        parent::tearDown();
    }

    public function test_prunes_old_local_backup_artifacts_after_retention_days(): void
    {
        Storage::fake('private');
        Date::setTestNow('2026-06-06 10:00:00');
        SystemSetting::put('backups.retention_days', 30);

        $backupRun = BackupRun::query()->create([
            'status' => 'succeeded',
            'destination' => 'local',
            'started_at' => now()->subDays(40),
            'finished_at' => now()->subDays(40),
            'size_bytes' => 1024,
            'meta' => [
                'database_path' => 'backups/old-backup/database.dump',
                'manifest_path' => 'backups/old-backup/manifest.json',
                'rollback_available' => true,
            ],
        ]);

        Storage::disk('private')->put('backups/old-backup/database.dump', 'database');
        Storage::disk('private')->put('backups/old-backup/manifest.json', '{}');

        $result = app(LocalBackupPruner::class)->prune();

        Storage::disk('private')->assertMissing('backups/old-backup/database.dump');
        Storage::disk('private')->assertMissing('backups/old-backup/manifest.json');

        $this->assertSame(1, $result['pruned']);
        $this->assertDatabaseHas('backup_runs', [
            'id' => $backupRun->id,
            'status' => 'pruned',
        ]);
        $this->assertFalse((bool) $backupRun->fresh()->meta['rollback_available']);
        $this->assertTrue((bool) $backupRun->fresh()->meta['artifact_pruned']);
    }

    public function test_pruner_keeps_recent_failed_running_and_non_local_backups(): void
    {
        Storage::fake('private');
        Date::setTestNow('2026-06-06 10:00:00');
        SystemSetting::put('backups.retention_days', 30);

        $runs = [
            ['key' => 'recent-backup', 'status' => 'succeeded', 'destination' => 'local', 'finished_at' => now()->subDays(2)],
            ['key' => 'failed-backup', 'status' => 'failed', 'destination' => 'local', 'finished_at' => now()->subDays(40)],
            ['key' => 'running-backup', 'status' => 'running', 'destination' => 'local', 'finished_at' => null],
            ['key' => 'external-backup', 'status' => 'succeeded', 'destination' => 's3', 'finished_at' => now()->subDays(40)],
        ];
        $backupRuns = [];

        foreach ($runs as $run) {
            $backupRun = BackupRun::query()->create([
                'status' => $run['status'],
                'destination' => $run['destination'],
                'started_at' => now()->subDays(40),
                'finished_at' => $run['finished_at'],
                'meta' => [
                    'database_path' => "backups/{$run['key']}/database.dump",
                    'manifest_path' => "backups/{$run['key']}/manifest.json",
                    'rollback_available' => true,
                ],
            ]);
            $backupRuns[$run['key']] = $backupRun;

            Storage::disk('private')->put("backups/{$run['key']}/database.dump", 'database');
            Storage::disk('private')->put("backups/{$run['key']}/manifest.json", '{}');
        }

        $result = app(LocalBackupPruner::class)->prune();

        $this->assertSame(0, $result['pruned']);

        foreach ($runs as $run) {
            Storage::disk('private')->assertExists("backups/{$run['key']}/database.dump");
            Storage::disk('private')->assertExists("backups/{$run['key']}/manifest.json");
            $this->assertDatabaseHas('backup_runs', [
                'uuid' => $backupRuns[$run['key']]->uuid,
                'status' => $run['status'],
            ]);
        }
    }
}
