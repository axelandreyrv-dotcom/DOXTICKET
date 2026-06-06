<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
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

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        $memberships = $user->activeMemberships()
            ->with('company')
            ->orderBy('id')
            ->get();

        if ($memberships->count() === 1) {
            $membership = $memberships->first();
            $request->session()->put('active_membership_id', $membership->id);
            $membership->forceFill(['last_selected_at' => now()])->save();
            $user->forceFill(['last_active_company_id' => $membership->company_id])->save();

            return redirect()->intended('/app/tickets');
        }

        if ($memberships->count() > 1) {
            $request->session()->forget('active_membership_id');

            return redirect('/app/companies');
        }

        if ($user->is_superadmin) {
            return redirect('/admin');
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
