<?php

namespace Tests\Feature\Admin;

use App\Mail\Admin\UserInvitationMail;
use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_users(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_users(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_superadmin_can_view_users_with_memberships_summary(): void
    {
        $superadmin = User::factory()->create([
            'name' => 'Root Admin',
            'email' => 'root@example.test',
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['name' => 'QA Company']);
        $agent = User::factory()->create([
            'name' => 'Ana Mesa',
            'email' => 'ana@example.test',
            'is_active' => true,
        ]);
        Membership::factory()->for($company)->for($agent)->create([
            'role' => 'agent',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Usuarios')
            ->assertSee('Root Admin')
            ->assertSee('Superadmin')
            ->assertSee('Ana Mesa')
            ->assertSee('ana@example.test')
            ->assertSee('QA Company')
            ->assertSee('agent')
            ->assertSee('Activa')
            ->assertSee('Powered by DoxTicket');
    }

    public function test_admin_dashboard_links_to_users_panel(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Usuarios')
            ->assertSee('href="'.route('admin.users.index').'"', false);
    }

    public function test_superadmin_can_deactivate_and_reactivate_user(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($superadmin)
            ->post("/admin/users/{$user->id}/status", [
                'is_active' => '0',
            ])
            ->assertRedirect('/admin/users');

        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($superadmin)
            ->post("/admin/users/{$user->id}/status", [
                'is_active' => '1',
            ])
            ->assertRedirect('/admin/users');

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_superadmin_cannot_deactivate_own_account(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post("/admin/users/{$superadmin->id}/status", [
                'is_active' => '0',
            ])
            ->assertRedirect('/admin/users')
            ->assertSessionHas('status', 'No puedes desactivar tu propia cuenta.');

        $this->assertTrue($superadmin->fresh()->is_active);
    }

    public function test_user_status_actions_include_confirmation_dialog(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        User::factory()->create(['is_active' => true]);

        $this->actingAs($superadmin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('data-confirm="Cambiar el estado global de este usuario puede afectar su acceso. ¿Continuar?"', false)
            ->assertSee('id="confirm-dialog"', false)
            ->assertSee('aria-labelledby="confirm-dialog-title"', false)
            ->assertSee('aria-describedby="confirm-dialog-message"', false);
    }

    public function test_superadmin_can_update_membership_role_and_status(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create();
        $agent = User::factory()->create();
        $membership = Membership::factory()->for($company)->for($agent)->create([
            'role' => 'agent',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->put("/admin/memberships/{$membership->id}", [
                'role' => 'supervisor',
                'status' => 'disabled',
            ])
            ->assertRedirect('/admin/users');

        $membership->refresh();

        $this->assertSame('supervisor', $membership->role);
        $this->assertSame('disabled', $membership->status);
    }

    public function test_membership_role_and_status_must_be_allowed_values(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $membership = Membership::factory()->create([
            'role' => 'agent',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->put("/admin/memberships/{$membership->id}", [
                'role' => 'owner',
                'status' => 'deleted',
            ])
            ->assertInvalid(['role', 'status']);
    }

    public function test_superadmin_cannot_disable_last_active_admin_membership_for_company(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create();
        $admin = User::factory()->create();
        $membership = Membership::factory()->for($company)->for($admin)->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->put("/admin/memberships/{$membership->id}", [
                'role' => 'admin',
                'status' => 'disabled',
            ])
            ->assertRedirect('/admin/users')
            ->assertSessionHas('status', 'No puedes dejar la empresa sin un admin activo.');

        $this->assertSame('active', $membership->fresh()->status);
    }

    public function test_membership_management_forms_are_visible_in_users_panel(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $membership = Membership::factory()->create([
            'role' => 'agent',
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('action="'.route('admin.memberships.update', $membership).'"', false)
            ->assertSee('name="role"', false)
            ->assertSee('name="status"', false)
            ->assertSee('data-confirm="Cambiar esta membresia puede afectar el acceso del usuario a la empresa. ¿Continuar?"', false);
    }

    public function test_superadmin_can_view_invite_user_form(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        Company::factory()->create(['name' => 'QA Company']);

        $this->actingAs($superadmin)
            ->get('/admin/users/invite')
            ->assertOk()
            ->assertSee('Invitar usuario')
            ->assertSee('QA Company')
            ->assertSee('name="email"', false)
            ->assertSee('name="company_id"', false)
            ->assertSee('name="role"', false);
    }

    public function test_superadmin_can_invite_new_user_to_company(): void
    {
        Mail::fake();

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create();

        $this->actingAs($superadmin)
            ->post('/admin/users/invite', [
                'name' => 'Nuevo Agente',
                'email' => 'nuevo@example.test',
                'company_id' => $company->id,
                'role' => 'agent',
            ])
            ->assertRedirect('/admin/users');

        $user = User::query()->where('email', 'nuevo@example.test')->firstOrFail();

        $this->assertSame('Nuevo Agente', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertDatabaseHas('memberships', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'role' => 'agent',
            'status' => 'invited',
            'invited_by_user_id' => $superadmin->id,
        ]);
        $this->assertNotNull($user->memberships()->first()?->invited_at);

        Mail::assertSent(UserInvitationMail::class, function (UserInvitationMail $mail) use ($company): bool {
            return $mail->hasTo('nuevo@example.test')
                && $mail->membership->company_id === $company->id
                && $mail->membership->role === 'agent'
                && is_string($mail->passwordSetupUrl)
                && str_contains($mail->passwordSetupUrl, '/password/reset/');
        });
    }

    public function test_inviting_existing_user_reuses_account_and_adds_membership(): void
    {
        Mail::fake();

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create();
        $existing = User::factory()->create([
            'name' => 'Nombre Existente',
            'email' => 'existente@example.test',
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/users/invite', [
                'name' => 'Nombre Nuevo',
                'email' => 'existente@example.test',
                'company_id' => $company->id,
                'role' => 'supervisor',
            ])
            ->assertRedirect('/admin/users');

        $this->assertSame('Nombre Existente', $existing->fresh()->name);
        $this->assertDatabaseHas('memberships', [
            'company_id' => $company->id,
            'user_id' => $existing->id,
            'role' => 'supervisor',
            'status' => 'invited',
        ]);

        Mail::assertSent(UserInvitationMail::class, fn (UserInvitationMail $mail): bool => $mail->hasTo('existente@example.test')
            && $mail->passwordSetupUrl === null);
    }

    public function test_invite_rejects_duplicate_membership_for_same_company(): void
    {
        Mail::fake();

        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create();
        $user = User::factory()->create(['email' => 'duplicado@example.test']);
        Membership::factory()->for($company)->for($user)->create();

        $this->actingAs($superadmin)
            ->post('/admin/users/invite', [
                'name' => 'Duplicado',
                'email' => 'duplicado@example.test',
                'company_id' => $company->id,
                'role' => 'agent',
            ])
            ->assertRedirect('/admin/users/invite')
            ->assertSessionHas('status', 'Este usuario ya pertenece a la empresa seleccionada.');

        $this->assertSame(1, Membership::query()->where('company_id', $company->id)->where('user_id', $user->id)->count());
        Mail::assertNothingSent();
    }

    public function test_regular_user_cannot_invite_users_from_admin(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->post('/admin/users/invite', [
                'name' => 'Sin Permiso',
                'email' => 'sinpermiso@example.test',
                'company_id' => Company::factory()->create()->id,
                'role' => 'agent',
            ])
            ->assertForbidden();
    }
}
