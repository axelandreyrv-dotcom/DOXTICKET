<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::broker()->sendResetLink($validated);

        return redirect('/login')
            ->with('status', 'Si el correo existe, enviaremos un enlace para restablecer la contraseña.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ]);

        $status = Password::broker()->reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->memberships()
                    ->where('status', 'invited')
                    ->get()
                    ->each(function (Membership $membership): void {
                        $membership->forceFill([
                            'status' => 'active',
                            'accepted_at' => now(),
                        ])->save();

                        AuditLog::query()->create([
                            'company_id' => $membership->company_id,
                            'actor_user_id' => $membership->user_id,
                            'actor_membership_id' => $membership->id,
                            'action' => 'membership.accepted',
                            'subject_type' => Membership::class,
                            'subject_id' => $membership->id,
                            'meta' => [
                                'accepted_via' => 'password_reset',
                            ],
                        ]);
                    });

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'El enlace para definir contraseña no es valido o ya vencio.',
            ]);
        }

        return redirect('/login')
            ->with('status', 'Contraseña actualizada. Ya puedes entrar.');
    }
}
