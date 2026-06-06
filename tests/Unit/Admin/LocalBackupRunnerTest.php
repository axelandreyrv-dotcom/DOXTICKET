<?php

namespace Tests\Unit\Admin;

use App\Services\Admin\LocalBackupRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocalBackupRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_runner_writes_private_sqlite_backup_and_records_success(): void
    {
        Storage::fake('private');

        $databasePath = storage_path('framework/testing-backup.sqlite');
        file_put_contents($databasePath, 'sqlite-backup-content');

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $databasePath);

        $backupRun = app(LocalBackupRunner::class)->run('manual');

        $this->assertSame('succeeded', $backupRun->status);
        $this->assertSame('local', $backupRun->destination);
        $this->assertNotNull($backupRun->started_at);
        $this->assertNotNull($backupRun->finished_at);
        $this->assertGreaterThan(0, $backupRun->size_bytes);
        $this->assertSame('sqlite', $backupRun->meta['database_driver']);
        $this->assertTrue($backupRun->meta['rollback_available']);

        Storage::disk('private')->assertExists($backupRun->meta['manifest_path']);
        Storage::disk('private')->assertExists($backupRun->meta['database_path']);

        @unlink($databasePath);
    }
}
