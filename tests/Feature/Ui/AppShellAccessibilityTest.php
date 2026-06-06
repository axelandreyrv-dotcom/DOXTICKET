<?php

namespace Tests\Feature\Ui;

use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppShellAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_navigation_has_label_and_marks_current_page(): void
    {
        [$user, $membership] = $this->tenantFixture();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.tickets.index'))
            ->assertOk()
            ->assertSee('aria-label="Navegación principal"', false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('Dashboard')
            ->assertSee('Tickets');
    }

    public function test_app_shell_uses_brand_logo_and_svg_favicon(): void
    {
        [$user, $membership] = $this->tenantFixture();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.tickets.index'))
            ->assertOk()
            ->assertSee('rel="icon"', false)
            ->assertSee('href="'.asset('brand/doxticket.svg').'"', false)
            ->assertSee('alt=""', false);
    }

    public function test_superadmin_membership_does_not_see_admin_link_inside_app_shell(): void
    {
        [$user, $membership] = $this->tenantFixture(isSuperadmin: true);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.tickets.index'))
            ->assertOk()
            ->assertDontSee(route('admin.dashboard'), false)
            ->assertDontSee('Admin');
    }

    public function test_app_navigation_keeps_ticket_workspace_compact(): void
    {
        [$user, $membership] = $this->tenantFixture(isSuperadmin: true);

        $response = $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.tickets.index'))
            ->assertOk()
            ->assertSee('Tickets')
            ->assertSee('Actividad')
            ->assertSee('Salir')
            ->assertDontSee(route('app.companies'), false)
            ->assertDontSee(route('app.settings'), false)
            ->assertDontSee('Empresa')
            ->assertDontSee('Configuración')
            ->assertDontSee('Base')
            ->assertDontSee('Dashboard')
            ->assertDontSee('Admin');

        $this->assertSame(1, substr_count($response->getContent(), 'Navegación principal'));
    }

    public function test_regular_user_does_not_see_admin_link_inside_app_shell(): void
    {
        [$user, $membership] = $this->tenantFixture();

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get(route('app.tickets.index'))
            ->assertOk()
            ->assertDontSee(route('admin.dashboard'), false)
            ->assertDontSee('Admin');
    }

    public function test_app_flash_status_is_announced_once_for_assistive_technology(): void
    {
        [$user, $membership] = $this->tenantFixture();

        $response = $this->actingAs($user)
            ->withSession([
                'active_membership_id' => $membership->id,
                'status' => 'ticket-assigned',
            ])
            ->get(route('app.tickets.index'))
            ->assertOk()
            ->assertSee('role="status"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('data-app-flash-status', false)
            ->assertSee('Ticket asignado.');

        $this->assertSame(1, substr_count($response->getContent(), 'data-app-flash-status'));
    }

    public function test_form_field_errors_are_announced_and_associated_to_inputs(): void
    {
        [$user, $membership] = $this->tenantFixture();

        $this->followingRedirects()
            ->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->from(route('app.kb.create'))
            ->post(route('app.kb.store'), [
                'title' => '',
                'body_markdown' => 'Contenido interno.',
                'status' => 'published',
            ])
            ->assertOk()
            ->assertSee('id="title"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="title-error"', false)
            ->assertSee('id="title-error"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('El campo título es obligatorio.');
    }

    /**
     * @return array{User, Membership}
     */
    private function tenantFixture(bool $isSuperadmin = false): array
    {
        $user = User::factory()->create([
            'is_superadmin' => $isSuperadmin,
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['name' => 'Dox IT']);
        $membership = Membership::factory()->for($user)->for($company)->create(['role' => 'admin', 'status' => 'active']);

        return [$user, $membership];
    }
}
