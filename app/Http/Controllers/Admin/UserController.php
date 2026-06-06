<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\Admin\UserInvitationMail;
use App\Models\Company;
use App\Models\Membership;
use App\Models\User;
use App\Services\Admin\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with(['memberships.company'])
            ->orderBy('name')
            ->paginate(25);

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function invite(): View
    {
        return view('admin.users.invite', [
            'companies' => Company::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeInvite(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'company_id' => ['required', Rule::exists('companies', 'id')->where('status', 'active')],
            'role' => ['required', Rule::in(['admin', 'supervisor', 'agent'])],
        ]);

        $email = Str::lower($validated['email']);
        $user = User::query()->where('email', $email)->first();
        $isNewUser = $user === null;

        if ($user && Membership::query()->where('company_id', $validated['company_id'])->where('user_id', $user->id)->exists()) {
            return redirect()
                ->route('admin.users.invite')
                ->withInput()
                ->with('status', 'Este usuario ya pertenece a la empresa seleccionada.');
        }

        $user ??= User::query()->create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'is_active' => true,
            'locale' => 'es',
        ]);

        $membership = Membership::query()->create([
            'company_id' => $validated['company_id'],
            'user_id' => $user->id,
            'role' => $validated['role'],
            'status' => 'invited',
            'invited_by_user_id' => $request->user()?->id,
            'invited_at' => now(),
            'preferences' => [],
        ]);

        $passwordSetupUrl = $isNewUser ? $this->passwordSetupUrl($user) : null;
        $mailSent = $this->sendInvitationEmail($membership, $passwordSetupUrl);

        $auditLogger->record($request, 'admin.user.invited', $membership, [
            'invited_user_id' => $user->id,
            'role' => $membership->role,
            'is_new_user' => $isNewUser,
            'mail_sent' => $mailSent,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', $mailSent
                ? 'Invitacion registrada y correo enviado.'
                : 'Invitacion registrada. No se pudo enviar el correo; revisa SMTP global.');
    }

    private function passwordSetupUrl(User $user): string
    {
        return route('password.reset', [
            'token' => Password::broker()->createToken($user),
            'email' => $user->email,
        ]);
    }

    private function sendInvitationEmail(Membership $membership, ?string $passwordSetupUrl): bool
    {
        try {
            Mail::to($membership->user->email)->send(new UserInvitationMail($membership, $passwordSetupUrl));

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function updateStatus(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', Rule::in(['0', '1'])],
        ]);

        if ($request->user()?->is($user) && $validated['is_active'] === '0') {
            return redirect()
                ->route('admin.users.index')
                ->with('status', 'No puedes desactivar tu propia cuenta.');
        }

        $previousStatus = $user->is_active;

        $user->update([
            'is_active' => $validated['is_active'] === '1',
        ]);

        $auditLogger->record($request, 'admin.user.status_changed', $user, [
            'from' => $previousStatus,
            'to' => $user->is_active,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Estado de usuario actualizado.');
    }
}
