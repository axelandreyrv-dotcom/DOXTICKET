<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuditLogger;
use App\Services\Auth\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function start(Request $request, Totp $totp): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ], [], [
            'current_password' => 'contraseña actual',
        ]);

        $request->user()->forceFill([
            'two_factor_secret' => $totp->generateSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect('/app/settings')->with('status', 'two-factor-started');
    }

    public function confirm(Request $request, Totp $totp, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:16'],
        ], [], [
            'code' => 'código',
        ]);

        $user = $request->user();

        if (blank($user->two_factor_secret) || ! $totp->verify((string) $user->two_factor_secret, (string) $data['code'])) {
            throw ValidationException::withMessages([
                'code' => 'El código de verificación no es válido.',
            ]);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $totp->recoveryCodes(),
        ])->save();

        $auditLogger->record($request, 'auth.two_factor_enabled', $user);

        return redirect('/app/settings')->with('status', 'two-factor-enabled');
    }

    public function destroy(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
        ], [], [
            'current_password' => 'contraseña actual',
        ]);

        if (! Hash::check((string) $data['current_password'], (string) $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'La contraseña actual no es correcta.',
            ]);
        }

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $auditLogger->record($request, 'auth.two_factor_disabled', $request->user());

        return redirect('/app/settings')->with('status', 'two-factor-disabled');
    }
}
