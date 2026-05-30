<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_one_active_membership_logs_in_and_gets_active_company(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secure-password'),
        ]);
        $company = Company::factory()->create();
        $membership = Membership::factory()->for($company)->for($user)->create([
            'role' => 'agent',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password',
        ]);

        $response->assertRedirect('/app/dashboard');
        $response->assertSessionHas('active_membership_id', $membership->id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_with_multiple_memberships_must_choose_company(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secure-password'),
        ]);
        Membership::factory()->for($user)->for(Company::factory())->create();
        Membership::factory()->for($user)->for(Company::factory())->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password',
        ]);

        $response->assertRedirect('/app/companies');
        $response->assertSessionMissing('active_membership_id');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_uses_generic_error_and_does_not_authenticate(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secure-password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
