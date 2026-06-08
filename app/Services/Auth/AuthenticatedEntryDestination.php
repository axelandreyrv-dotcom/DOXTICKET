<?php

namespace App\Services\Auth;

use App\Models\Membership;
use Illuminate\Http\Request;

class AuthenticatedEntryDestination
{
    public function resolve(Request $request): string
    {
        $user = $request->user();

        if ($user?->is_superadmin === true && $user->is_active === true) {
            return '/admin';
        }

        $activeMembershipId = $request->session()->get('active_membership_id');

        if ($activeMembershipId !== null) {
            $membership = $this->validMembershipForUser((int) $activeMembershipId, (int) $user?->id);

            if ($membership !== null) {
                return '/app/tickets';
            }

            $request->session()->forget('active_membership_id');
        }

        $memberships = $user?->activeMemberships()
            ->with('company')
            ->orderBy('id')
            ->get() ?? collect();

        if ($memberships->count() === 1) {
            $membership = $memberships->first();
            $request->session()->put('active_membership_id', $membership->id);
            $membership->forceFill(['last_selected_at' => now()])->save();
            $user?->forceFill(['last_active_company_id' => $membership->company_id])->save();

            return '/app/tickets';
        }

        $request->session()->forget('active_membership_id');

        return '/app/companies';
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
