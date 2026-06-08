<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\MailAccount;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompaniesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_companies(): void
    {
        $this->get('/admin/companies')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_companies(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->get('/admin/companies')
            ->assertForbidden();
    }

    public function test_superadmin_can_view_companies_with_operational_summary(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $company = Company::factory()->create([
            'name' => 'QA Company',
            'status' => 'active',
            'locale_default' => 'es',
        ]);
        $archivedCompany = Company::factory()->create([
            'name' => 'Archive Company',
            'status' => 'archived',
        ]);

        Membership::factory()->count(2)->for($company)->create(['status' => 'active']);
        Membership::factory()->for($archivedCompany)->create(['status' => 'active']);
        Ticket::factory()->count(2)->for($company)->create(['status' => 'open']);
        Ticket::factory()->for($archivedCompany)->create(['status' => 'closed']);
        MailAccount::factory()->for($company)->create([
            'from_email' => 'soporte@qa.test',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/companies')
            ->assertOk()
            ->assertSee('Empresas')
            ->assertSee('QA Company')
            ->assertSee('Archive Company')
            ->assertSee('Activa')
            ->assertSee('Archivada')
            ->assertSee('soporte@qa.test')
            ->assertSee('2 miembros')
            ->assertSee('2 tickets')
            ->assertSee('Sin correo activo');
    }

    public function test_admin_dashboard_links_to_companies_panel(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Empresas')
            ->assertSee('href="'.route('admin.companies.index').'"', false);
    }

    public function test_superadmin_can_create_company_from_admin_panel(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/companies', [
                'name' => 'OMU Soporte',
                'slug' => 'omu-soporte',
                'country' => 'CR',
                'locale_default' => 'es',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/companies');

        $this->assertDatabaseHas('companies', [
            'name' => 'OMU Soporte',
            'slug' => 'omu-soporte',
            'country' => 'CR',
            'locale_default' => 'es',
            'status' => 'active',
        ]);
    }

    public function test_superadmin_can_create_company_with_full_country_name(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post('/admin/companies', [
                'name' => 'Once Mil Uno',
                'slug' => 'once-mil-uno',
                'country' => 'Costa Rica',
                'locale_default' => 'es',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/companies');

        $this->assertDatabaseHas('companies', [
            'name' => 'Once Mil Uno',
            'slug' => 'once-mil-uno',
            'country' => 'Costa Rica',
        ]);
    }

    public function test_company_slug_must_be_unique_when_created_from_admin_panel(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        Company::factory()->create(['slug' => 'omu-soporte']);

        $this->actingAs($superadmin)
            ->post('/admin/companies', [
                'name' => 'OMU Soporte',
                'slug' => 'omu-soporte',
                'locale_default' => 'es',
                'status' => 'active',
            ])
            ->assertInvalid('slug');
    }

    public function test_superadmin_can_edit_company_without_changing_tenant_context(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $activeCompany = Company::factory()->create();
        $targetCompany = Company::factory()->create([
            'name' => 'Mesa antigua',
            'slug' => 'mesa-antigua',
            'status' => 'active',
        ]);

        $this->withSession(['active_company_id' => $activeCompany->id])
            ->actingAs($superadmin)
            ->put("/admin/companies/{$targetCompany->id}", [
                'name' => 'Mesa actualizada',
                'slug' => 'mesa-actualizada',
                'country' => 'CR',
                'phone' => '+506 2200 0000',
                'locale_default' => 'es',
                'status' => 'disabled',
            ])
            ->assertRedirect('/admin/companies');

        $this->assertDatabaseHas('companies', [
            'id' => $targetCompany->id,
            'name' => 'Mesa actualizada',
            'slug' => 'mesa-actualizada',
            'status' => 'disabled',
        ]);
    }

    public function test_superadmin_can_change_company_status_to_allowed_values_only(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['status' => 'active']);

        $this->actingAs($superadmin)
            ->post("/admin/companies/{$company->id}/status", [
                'status' => 'archived',
            ])
            ->assertRedirect('/admin/companies');

        $this->assertSame('archived', $company->fresh()->status);

        $this->actingAs($superadmin)
            ->post("/admin/companies/{$company->id}/status", [
                'status' => 'deleted',
            ])
            ->assertInvalid('status');
    }

    public function test_superadmin_can_soft_delete_company_from_admin_panel(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create([
            'name' => 'Empresa para borrar',
            'slug' => 'empresa-para-borrar',
            'status' => 'active',
        ]);
        $userWithLastCompany = User::factory()->create([
            'last_active_company_id' => $company->id,
        ]);

        $this->actingAs($superadmin)
            ->delete("/admin/companies/{$company->id}")
            ->assertRedirect('/admin/companies')
            ->assertSessionHas('status', 'Empresa eliminada.');

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
        $this->assertNull($userWithLastCompany->fresh()->last_active_company_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'admin.company.deleted',
            'actor_user_id' => $superadmin->id,
            'subject_type' => Company::class,
            'subject_id' => $company->id,
        ]);

        $this->actingAs($superadmin)
            ->get('/admin/companies')
            ->assertOk()
            ->assertDontSee('Empresa para borrar');
    }

    public function test_regular_user_cannot_delete_company(): void
    {
        $user = User::factory()->create([
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        $company = Company::factory()->create();

        $this->actingAs($user)
            ->delete("/admin/companies/{$company->id}")
            ->assertForbidden();

        $this->assertNotSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_companies_panel_links_to_create_and_edit_screens(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        $company = Company::factory()->create(['name' => 'QA Company']);

        $this->actingAs($superadmin)
            ->get('/admin/companies')
            ->assertOk()
            ->assertSee('Nueva empresa')
            ->assertSee('href="'.route('admin.companies.create').'"', false)
            ->assertSee('href="'.route('admin.companies.edit', $company).'"', false);
    }

    public function test_company_status_actions_include_confirmation_dialog(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'is_active' => true,
        ]);
        Company::factory()->create(['status' => 'active']);

        $this->actingAs($superadmin)
            ->get('/admin/companies')
            ->assertOk()
            ->assertSee('data-confirm="Cambiar el estado de esta empresa puede afectar el acceso operativo. ¿Continuar?"', false)
            ->assertSee('data-confirm="Eliminar esta empresa oculta el tenant y bloquea su acceso operativo. Los datos se conservan para auditoria. ¿Continuar?"', false)
            ->assertSee('id="confirm-dialog"', false)
            ->assertSee('aria-labelledby="confirm-dialog-title"', false)
            ->assertSee('aria-describedby="confirm-dialog-message"', false);
    }
}
