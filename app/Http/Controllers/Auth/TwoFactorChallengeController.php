<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.two_factor_user_id')) {
            return redirect('/login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, Totp $totp): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:32'],
            'recovery_code' => ['nullable', 'string', 'max:32'],
        ], [], [
            'code' => 'código',
            'recovery_code' => 'código de recuperación',
        ]);

        $user = User::query()->find($request->session()->get('login.two_factor_user_id'));

        if (! $user || ! $user->is_active || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['login.two_factor_user_id', 'login.remember']);

            return redirect('/login');
        }

        $code = trim((string) ($data['code'] ?? ''));
        $recoveryCode = trim((string) ($data['recovery_code'] ?? ''));

        $valid = $code !== '' && $totp->verify((string) $user->two_factor_secret, $code);

        if (! $valid && $recoveryCode !== '') {
            $valid = $this->consumeRecoveryCode($user, $recoveryCode);
        }

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => 'El código de verificación no es válido.',
            ]);
        }

        Auth::login($user, $request->session()->pull('login.remember', false));
        $request->session()->forget('login.two_factor_user_id');
        $request->session()->regenerate();

        return app(AuthenticatedSessionController::class)->completeLogin($request, $user);
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];
        $match = collect($codes)->first(fn (string $stored): bool => hash_equals($stored, $code));

        if ($match === null) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => array_values(array_filter(
                $codes,
                fn (string $stored): bool => ! hash_equals($stored, $code),
            )),
        ])->save();

        return true;
    }
}
