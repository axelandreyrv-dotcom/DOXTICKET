<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_settings(): void
    {
        $this->get('/admin/settings')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_settings(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_superadmin_can_view_safe_installation_settings(): void
    {
        Config::set('app.url', 'https://doxticket.test');
        Config::set('doxticket.version', 'v1.0.0');
        Config::set('doxticket.updates.github_repository', 'doxsuite/doxticket');
        SystemSetting::put('telemetry.enabled', true);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Configuración')
            ->assertSee('Instalación')
            ->assertSee('https://doxticket.test')
            ->assertSee('v1.0.0')
            ->assertSee('doxsuite/doxticket')
            ->assertSee('Telemetría')
            ->assertSee('Activa')
            ->assertSee('Correo global');
    }

    public function test_superadmin_can_update_public_installation_settings(): void
    {
        Config::set('app.url', 'https://old.example.test');
        Config::set('doxticket.updates.github_repository', 'doxsuite/doxticket');

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/settings', [
                'public_url' => 'https://helpdesk.example.test',
                'github_repository' => 'acme/doxticket',
                'backup_recent_success_hours' => 48,
                'backup_retention_days' => 30,
                'backup_schedule_enabled' => '1',
                'backup_schedule_hour' => 3,
            ])
            ->assertRedirect('/admin/settings')
            ->assertSessionHas('status', 'Configuración actualizada.');

        $this->assertSame('https://helpdesk.example.test', SystemSetting::get('installation.public_url'));
        $this->assertSame('acme/doxticket', SystemSetting::get('updates.github_repository'));
        $this->assertSame(48, SystemSetting::get('backups.recent_success_hours'));
        $this->assertSame(30, SystemSetting::get('backups.retention_days'));
        $this->assertTrue(SystemSetting::get('backups.schedule_enabled'));
        $this->assertSame(3, SystemSetting::get('backups.schedule_hour'));

        $auditLog = AuditLog::query()->where('action', 'admin.settings.updated')->first();

        $this->assertNotNull($auditLog);
        $this->assertSame($superadmin->id, $auditLog->actor_user_id);
        $this->assertSame([
            'installation.public_url',
            'updates.github_repository',
            'backups.recent_success_hours',
            'backups.retention_days',
            'backups.schedule_enabled',
            'backups.schedule_hour',
        ], $auditLog->meta['changed_keys']);
    }

    public function test_superadmin_sees_saved_backup_policy_settings(): void
    {
        SystemSetting::put('backups.recent_success_hours', 72);
        SystemSetting::put('backups.retention_days', 45);
        SystemSetting::put('backups.schedule_enabled', true);
        SystemSetting::put('backups.schedule_hour', 4);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Política de backups')
            ->assertSee('value="72"', false)
            ->assertSee('value="45"', false)
            ->assertSee('Backup automático')
            ->assertSee('value="4"', false)
            ->assertSee('checked', false);
    }

    public function test_settings_update_rejects_unsafe_urls_and_invalid_repository_names(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->from('/admin/settings')
            ->post('/admin/settings', [
                'public_url' => 'javascript:alert(1)',
                'github_repository' => 'bad repo',
                'backup_recent_success_hours' => 0,
                'backup_retention_days' => 366,
                'backup_schedule_enabled' => '1',
                'backup_schedule_hour' => 24,
            ])
            ->assertRedirect('/admin/settings')
            ->assertInvalid([
                'public_url',
                'github_repository',
                'backup_recent_success_hours',
                'backup_retention_days',
                'backup_schedule_hour',
            ]);

        $this->assertNull(SystemSetting::get('installation.public_url'));
        $this->assertNull(SystemSetting::get('updates.github_repository'));
        $this->assertNull(SystemSetting::get('backups.recent_success_hours'));
        $this->assertNull(SystemSetting::get('backups.retention_days'));
        $this->assertNull(SystemSetting::get('backups.schedule_hour'));
    }

    public function test_superadmin_sees_saved_public_settings_without_secret_values(): void
    {
        Config::set('mail.mailers.smtp.password', 'smtp-secret-value');
        SystemSetting::put('installation.public_url', 'https://helpdesk.example.test');
        SystemSetting::put('updates.github_repository', 'acme/doxticket');

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('https://helpdesk.example.test')
            ->assertSee('acme/doxticket')
            ->assertDontSee('smtp-secret-value');
    }

    public function test_regular_user_cannot_update_admin_settings(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->post('/admin/settings', [
                'public_url' => 'https://helpdesk.example.test',
                'github_repository' => 'acme/doxticket',
            ])
            ->assertForbidden();
    }

    public function test_admin_dashboard_links_to_settings_panel(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Configuración')
            ->assertSee('href="'.route('admin.settings.index').'"', false);
    }
}
