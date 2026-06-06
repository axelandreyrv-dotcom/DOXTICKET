<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminManualBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_run_manual_backup_from_admin_panel(): void
    {
        Storage::fake('private');

        $databasePath = storage_path('framework/testing-admin-backup.sqlite');
        file_put_contents($databasePath, 'sqlite-admin-backup-content');

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $databasePath);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/backups')
            ->assertRedirect('/admin')
            ->assertSessionHas('status', 'Backup local generado.');

        $this->assertDatabaseHas('backup_runs', [
            'status' => 'succeeded',
            'destination' => 'local',
        ]);

        @unlink($databasePath);
    }

    public function test_regular_user_cannot_run_manual_backup(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/admin/backups')
            ->assertForbidden();
    }

    public function test_admin_panel_shows_manual_backup_button(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Ejecutar backup');
    }
}
