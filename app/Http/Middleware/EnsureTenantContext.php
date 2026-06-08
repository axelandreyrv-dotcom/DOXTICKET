<?php

namespace App\Http\Middleware;

use App\Models\Membership;
use App\Support\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $activeMembershipId = $request->session()->get('active_membership_id');

        if ($activeMembershipId !== null) {
            $membership = $this->validMembershipForUser((int) $activeMembershipId, $user->id);

            if ($membership !== null) {
                app(TenantContext::class)->set($membership);

                return $next($request);
            }

            $request->session()->forget('active_membership_id');
        }

        $memberships = $user->activeMemberships()->with('company')->orderBy('id')->get();

        if ($memberships->count() === 1) {
            $membership = $memberships->first();
            $request->session()->put('active_membership_id', $membership->id);
            app(TenantContext::class)->set($membership);

            return $next($request);
        }

        if ($memberships->count() > 1) {
            return redirect('/app/companies');
        }

        return redirect('/app/companies')
            ->with('status', 'No tienes empresas activas. Solicita acceso o crea una empresa desde el panel admin.');
    }

    private function validMembershipForUser(int $membershipId, int $userId): ?Membership
    {
        return Membership::query()
            ->with('company')
            ->whereKey($membershipId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereHas('company', fn ($query) => $query->where('status', 'active'))
            ->first();
    }
}
