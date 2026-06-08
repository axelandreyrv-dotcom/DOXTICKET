<?php

namespace Tests\Feature\Admin;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_superadmin_can_view_system_health_summary(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Estado del sistema')
            ->assertSee('PostgreSQL')
            ->assertSee('APP_KEY')
            ->assertSee('Setup')
            ->assertSee('Powered by DoxTicket');
    }

    public function test_admin_panel_uses_brand_logo_and_svg_favicon(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('rel="icon"', false)
            ->assertSee('href="'.asset('brand/doxticket.svg').'"', false)
            ->assertSee('alt=""', false);
    }

    public function test_admin_health_route_uses_same_protected_summary(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/health')
            ->assertOk()
            ->assertSee('Estado del sistema')
            ->assertSee('Colas');
    }

    public function test_superadmin_sees_cached_update_notice_when_new_version_exists(): void
    {
        SystemSetting::put('updates.latest', [
            'checked_at' => '2026-05-31T10:00:00Z',
            'installed_version' => 'v1.0.0',
            'latest_version' => 'v1.1.0',
            'update_available' => true,
            'release_url' => 'https://github.com/doxsuite/doxticket/releases/tag/v1.1.0',
            'release_name' => 'DoxTicket v1.1.0',
            'changelog' => 'Correcciones estables.',
            'error' => null,
        ]);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Nueva versión estable disponible')
            ->assertSee('v1.1.0')
            ->assertSee('Ver release');
    }

    public function test_superadmin_can_run_manual_update_check_from_admin_panel(): void
    {
        Config::set('doxticket.version', 'v1.0.0');
        Config::set('doxticket.updates.github_repository', 'doxsuite/doxticket');

        Http::fake([
            'https://api.github.com/repos/doxsuite/doxticket/releases/latest' => Http::response([
                'tag_name' => 'v1.2.0',
                'name' => 'DoxTicket v1.2.0',
                'html_url' => 'https://github.com/doxsuite/doxticket/releases/tag/v1.2.0',
                'body' => 'Release estable',
            ], 200),
        ]);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/updates/check')
            ->assertRedirect('/admin')
            ->assertSessionHas('status', 'Chequeo de actualizaciones completado.');

        $this->assertSame('v1.2.0', SystemSetting::get('updates.latest')['latest_version']);
        $this->assertTrue(SystemSetting::get('updates.latest')['update_available']);
    }

    public function test_regular_user_cannot_run_manual_update_check(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->post('/admin/updates/check')
            ->assertForbidden();
    }

    public function test_admin_panel_shows_manual_update_check_button(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Revisar actualizaciones')
            ->assertSee('action="'.route('admin.updates.check').'"', false);
    }
}
