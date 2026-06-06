<?php

namespace Tests\Feature\Admin;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTelemetryPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_sees_telemetry_status_and_privacy_summary(): void
    {
        SystemSetting::put('telemetry.enabled', false);

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Telemetría')
            ->assertSee('Apagada')
            ->assertSee('No envia nombres, correos, asuntos, cuerpos, adjuntos ni secretos');
    }

    public function test_superadmin_can_enable_and_disable_telemetry(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/telemetry', ['telemetry_enabled' => true])
            ->assertRedirect('/admin')
            ->assertSessionHas('status', 'Telemetría actualizada.');

        $this->assertTrue(SystemSetting::get('telemetry.enabled'));

        $this->actingAs($superadmin)
            ->post('/admin/telemetry', ['telemetry_enabled' => false])
            ->assertRedirect('/admin');

        $this->assertFalse(SystemSetting::get('telemetry.enabled'));
    }

    public function test_regular_user_cannot_change_telemetry(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/admin/telemetry', ['telemetry_enabled' => true])
            ->assertForbidden();
    }
}
