<?php

namespace Tests\Feature\Setup;

use App\Models\Company;
use App\Models\Membership;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_setup_creates_superadmin_company_and_blocks_setup(): void
    {
        $response = $this->post('/setup', [
            'locale' => 'es',
            'company_name' => 'Dox IT',
            'admin_name' => 'Axel Ruiz',
            'admin_email' => 'axel@example.test',
            'admin_password' => 'secure-password',
            'admin_password_confirmation' => 'secure-password',
            'telemetry_enabled' => false,
        ]);

        $response->assertRedirect('/login');

        $user = User::query()->where('email', 'axel@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertTrue($user->is_superadmin);
        $this->assertTrue($user->is_active);

        $company = Company::query()->where('name', 'Dox IT')->firstOrFail();
        $this->assertSame('active', $company->status);
        $this->assertSame('es', $company->locale_default);

        $membership = Membership::query()
            ->whereBelongsTo($company)
            ->whereBelongsTo($user)
            ->firstOrFail();

        $this->assertSame('admin', $membership->role);
        $this->assertSame('active', $membership->status);

        $this->assertSame(true, SystemSetting::get('setup.completed'));
        $this->assertSame(false, SystemSetting::get('telemetry.enabled'));

        $this->get('/setup')->assertRedirect('/login');
        $this->post('/setup', [])->assertForbidden();
    }

    public function test_setup_validation_errors_are_accessible_and_in_spanish(): void
    {
        $response = $this->followingRedirects()
            ->from('/setup')
            ->post('/setup', [
                'locale' => '',
                'company_name' => '',
                'admin_name' => '',
                'admin_email' => '',
                'admin_password' => '',
            ]);

        $response->assertOk()
            ->assertSee('id="company_name"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('aria-describedby="company_name-error"', false)
            ->assertSee('id="company_name-error"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('aria-live="polite"', false)
            ->assertSee('El campo empresa es obligatorio.');
    }
}
