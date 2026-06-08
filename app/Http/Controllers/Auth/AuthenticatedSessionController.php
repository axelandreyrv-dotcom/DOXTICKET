<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Auth\AuthenticatedEntryDestination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user() !== null) {
            return redirect()->route('app.entry');
        }

        return view('auth.placeholder');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $credentials['is_active'] = true;

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            Auth::logout();
            $request->session()->put('login.two_factor_user_id', $user->id);
            $request->session()->put('login.remember', $request->boolean('remember'));
            $request->session()->regenerateToken();

            return redirect('/two-factor-challenge');
        }

        $request->session()->regenerate();

        return $this->completeLogin($request, $user);
    }

    public function completeLogin(Request $request, User $user): RedirectResponse
    {
        $user->forceFill(['last_login_at' => now()])->save();

        $destination = app(AuthenticatedEntryDestination::class)->resolve($request);

        if ($destination !== '/app/companies' || $user->activeMemberships()->exists() || $user->is_superadmin) {
            return redirect()->intended($destination);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
