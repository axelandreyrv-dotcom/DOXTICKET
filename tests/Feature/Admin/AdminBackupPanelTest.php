<?php

namespace Tests\Feature\Admin;

use App\Models\BackupRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBackupPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_sees_latest_backup_and_rollback_button_when_backup_exists(): void
    {
        BackupRun::query()->create([
            'status' => 'succeeded',
            'destination' => 'local',
            'started_at' => now()->subMinutes(8),
            'finished_at' => now()->subMinutes(5),
            'size_bytes' => 1024 * 1024 * 24,
            'meta' => ['rollback_available' => true, 'version' => 'v1.0.0'],
        ]);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Backups')
            ->assertSee('Último backup')
            ->assertSee('local')
            ->assertSee('24 MB')
            ->assertSee('Rollback');
    }

    public function test_superadmin_sees_disabled_rollback_when_no_valid_backup_exists(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Sin backup exitoso')
            ->assertSee('Rollback no disponible');
    }

    public function test_superadmin_sees_recent_backup_history_without_private_artifact_paths(): void
    {
        BackupRun::query()->create([
            'status' => 'succeeded',
            'destination' => 'local',
            'started_at' => now()->subMinutes(20),
            'finished_at' => now()->subMinutes(18),
            'size_bytes' => 512,
            'meta' => [
                'database_path' => 'backups/private-uuid/database.dump',
                'manifest_path' => 'backups/private-uuid/manifest.json',
            ],
        ]);

        BackupRun::query()->create([
            'status' => 'failed',
            'destination' => 'local',
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(9),
            'error' => 'No se pudo generar backup PostgreSQL con pg_dump.',
        ]);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Historial de backups')
            ->assertSee('succeeded')
            ->assertSee('failed')
            ->assertSee('512 B')
            ->assertSee('No se pudo generar backup PostgreSQL con pg_dump.')
            ->assertDontSee('backups/private-uuid/database.dump')
            ->assertDontSee('manifest.json');
    }
}
