<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_form_is_visible_from_login(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Olvidé mi contraseña');

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Restablecer contraseña')
            ->assertSee('name="email"', false)
            ->assertSee('Powered by DoxTicket');
    }

    public function test_existing_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'agente@example.test']);

        $this->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => 'agente@example.test',
            ])
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'Si el correo existe, enviaremos un enlace para restablecer la contraseña.');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Restablecer contraseña en DoxTicket'
                && str_contains($mail->actionUrl, '/password/reset/')
                && str_contains($mail->actionUrl, 'email=agente%40example.test');
        });
    }

    public function test_unknown_email_gets_generic_password_reset_response(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => 'noexiste@example.test',
            ])
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'Si el correo existe, enviaremos un enlace para restablecer la contraseña.');

        Notification::assertNothingSent();
    }

    public function test_reset_form_is_visible_with_token_and_email(): void
    {
        $user = User::factory()->create(['email' => 'agente@example.test']);
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]))
            ->assertOk()
            ->assertSee('Definir contraseña')
            ->assertSee('name="token"', false)
            ->assertSee('name="email"', false)
            ->assertSee('Powered by DoxTicket');
    }

    public function test_user_can_set_password_with_valid_reset_token(): void
    {
        $user = User::factory()->create([
            'email' => 'agente@example.test',
            'password' => Hash::make('old-random-password'),
        ]);
        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'Contraseña actualizada. Ya puedes entrar.');

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));

        $this->followRedirects($response)
            ->assertSee('Contraseña actualizada. Ya puedes entrar.');
    }

    public function test_setting_password_accepts_pending_invitations(): void
    {
        $user = User::factory()->create([
            'email' => 'invitado@example.test',
            'password' => Hash::make('old-random-password'),
        ]);
        $company = Company::factory()->create();
        $membership = Membership::factory()->for($company)->for($user)->create([
            'role' => 'agent',
            'status' => 'invited',
        ]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect('/login');

        $this->assertSame('active', $membership->fresh()->status);
        $this->assertNotNull($membership->fresh()->accepted_at);
        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $company->id,
            'actor_user_id' => $user->id,
            'actor_membership_id' => $membership->id,
            'action' => 'membership.accepted',
            'subject_type' => Membership::class,
            'subject_id' => $membership->id,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'new-secure-password',
        ])->assertRedirect('/app/tickets');
    }
}
