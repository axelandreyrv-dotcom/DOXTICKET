<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use App\Services\Auth\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_two_factor_enabled_must_complete_challenge_after_password(): void
    {
        [$user] = $this->twoFactorUser();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password',
        ]);

        $response->assertRedirect('/two-factor-challenge');
        $response->assertSessionHas('login.two_factor_user_id', $user->id);
        $this->assertGuest();
    }

    public function test_valid_two_factor_code_completes_login_and_sets_active_company(): void
    {
        [$user, $membership] = $this->twoFactorUser();
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password',
        ]);

        $response = $this->post('/two-factor-challenge', [
            'code' => app(Totp::class)->code((string) $user->two_factor_secret),
        ]);

        $response->assertRedirect('/app/tickets');
        $response->assertSessionMissing('login.two_factor_user_id');
        $response->assertSessionHas('active_membership_id', $membership->id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_recovery_code_completes_login_once_and_is_consumed(): void
    {
        [$user] = $this->twoFactorUser(['recovery-one']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password',
        ]);

        $this->post('/two-factor-challenge', [
            'recovery_code' => 'recovery-one',
        ])->assertRedirect('/app/tickets');

        $this->assertSame([], $user->refresh()->two_factor_recovery_codes);

        auth()->logout();
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password',
        ]);

        $this->from('/two-factor-challenge')
            ->post('/two-factor-challenge', [
                'recovery_code' => 'recovery-one',
            ])
            ->assertRedirect('/two-factor-challenge')
            ->assertSessionHasErrors('code');
    }

    public function test_user_can_prepare_confirm_and_disable_two_factor_from_settings(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secure-password'),
        ]);
        $company = Company::factory()->create();
        $membership = Membership::factory()->for($user)->for($company)->create(['status' => 'active']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post('/app/settings/two-factor/start', [
                'current_password' => 'secure-password',
            ])
            ->assertRedirect('/app/settings')
            ->assertSessionHas('status', 'two-factor-started');

        $user->refresh();
        $rawSecret = DB::table('users')->whereKey($user->id)->value('two_factor_secret');

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotSame($user->two_factor_secret, $rawSecret);
        $this->assertFalse($user->hasTwoFactorEnabled());

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post('/app/settings/two-factor/confirm', [
                'code' => app(Totp::class)->code((string) $user->two_factor_secret),
            ])
            ->assertRedirect('/app/settings')
            ->assertSessionHas('status', 'two-factor-enabled');

        $this->assertTrue($user->refresh()->hasTwoFactorEnabled());
        $this->assertCount(8, $user->two_factor_recovery_codes);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.two_factor_enabled',
            'actor_user_id' => $user->id,
            'subject_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->delete('/app/settings/two-factor', [
                'current_password' => 'secure-password',
            ])
            ->assertRedirect('/app/settings')
            ->assertSessionHas('status', 'two-factor-disabled');

        $this->assertFalse($user->refresh()->hasTwoFactorEnabled());
        $this->assertNull($user->two_factor_secret);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.two_factor_disabled',
            'actor_user_id' => $user->id,
            'subject_id' => $user->id,
        ]);
    }

    public function test_two_factor_settings_ui_is_accessible_and_in_spanish(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $membership = Membership::factory()->for($user)->for($company)->create(['status' => 'active']);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->get('/app/settings')
            ->assertOk()
            ->assertSee('Verificación 2FA')
            ->assertSee('Preparar 2FA')
            ->assertSee('id="enable_two_factor_password"', false)
            ->assertSee('autocomplete="current-password"', false);
    }

    /**
     * @param  array<int, string>  $recoveryCodes
     * @return array{User, Membership}
     */
    private function twoFactorUser(array $recoveryCodes = ['recovery-one', 'recovery-two']): array
    {
        $user = User::factory()->create([
            'password' => Hash::make('secure-password'),
            'two_factor_secret' => app(Totp::class)->generateSecret(),
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ]);
        $company = Company::factory()->create();
        $membership = Membership::factory()->for($user)->for($company)->create(['status' => 'active']);

        return [$user, $membership];
    }
}
