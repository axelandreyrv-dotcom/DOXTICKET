<?php

namespace Tests\Feature\Admin;

use App\Models\BackupRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRollbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_request_rollback(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/admin/rollback')
            ->assertForbidden();
    }

    public function test_superadmin_cannot_request_rollback_without_valid_backup(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/rollback')
            ->assertRedirect('/admin')
            ->assertSessionHas('status', 'Rollback no disponible: falta backup valido.');
    }

    public function test_superadmin_can_request_manual_rollback_preflight_with_valid_backup(): void
    {
        BackupRun::query()->create([
            'status' => 'succeeded',
            'destination' => 'local',
            'started_at' => now()->subMinutes(20),
            'finished_at' => now()->subMinutes(18),
            'size_bytes' => 1024,
            'meta' => [
                'rollback_available' => true,
                'version' => 'v1.0.0',
                'database_path' => 'backups/private/database.dump',
            ],
        ]);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/rollback')
            ->assertRedirect('/admin')
            ->assertSessionHas('status', 'Rollback preparado: revisa la guia manual antes de restaurar.');
    }

    public function test_rollback_button_posts_when_valid_backup_exists(): void
    {
        BackupRun::query()->create([
            'status' => 'succeeded',
            'destination' => 'local',
            'started_at' => now()->subMinutes(20),
            'finished_at' => now()->subMinutes(18),
            'size_bytes' => 1024,
            'meta' => ['rollback_available' => true],
        ]);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('action="'.route('admin.rollback.store').'"', false)
            ->assertSee('Rollback');
    }
}
