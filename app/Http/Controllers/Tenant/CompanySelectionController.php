<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\SelectCompanyRequest;
use App\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySelectionController extends Controller
{
    public function index(Request $request): View
    {
        $memberships = $request->user()
            ->activeMemberships()
            ->with('company')
            ->orderBy('id')
            ->get();

        return view('app.companies', ['memberships' => $memberships]);
    }

    public function store(SelectCompanyRequest $request): RedirectResponse
    {
        $membership = Membership::query()
            ->with('company')
            ->whereKey($request->integer('membership_id'))
            ->firstOrFail();

        abort_unless(
            $membership->user_id === $request->user()->id
            && $membership->status === 'active'
            && $membership->company?->status === 'active',
            403,
        );

        $request->session()->put('active_membership_id', $membership->id);
        $membership->forceFill(['last_selected_at' => now()])->save();
        $request->user()->forceFill(['last_active_company_id' => $membership->company_id])->save();

        return redirect('/app/tickets');
    }
}
