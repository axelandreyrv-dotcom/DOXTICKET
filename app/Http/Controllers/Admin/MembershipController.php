<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MembershipController extends Controller
{
    public function update(Request $request, Membership $membership, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'supervisor', 'agent'])],
            'status' => ['required', Rule::in(['active', 'disabled'])],
        ]);

        if ($this->wouldLeaveCompanyWithoutActiveAdmin($membership, $validated['role'], $validated['status'])) {
            return redirect()
                ->route('admin.users.index')
                ->with('status', 'No puedes dejar la empresa sin un admin activo.');
        }

        $before = $membership->only(['role', 'status']);

        $membership->update($validated);

        $auditLogger->record($request, 'admin.membership.updated', $membership, [
            'before' => $before,
            'after' => $membership->only(['role', 'status']),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Membresia actualizada.');
    }

    public function destroy(Request $request, Membership $membership, AuditLogger $auditLogger): RedirectResponse
    {
        if ($this->wouldLeaveCompanyWithoutActiveAdmin($membership, 'agent', 'disabled')) {
            return redirect()
                ->route('admin.users.index')
                ->with('status', 'No puedes dejar la empresa sin un admin activo.');
        }

        $before = $membership->only(['company_id', 'user_id', 'role', 'status']);

        $membership->delete();

        $auditLogger->record($request, 'admin.membership.deleted', $membership, [
            'before' => $before,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Acceso a empresa eliminado.');
    }

    private function wouldLeaveCompanyWithoutActiveAdmin(Membership $membership, string $role, string $status): bool
    {
        if ($membership->role !== 'admin' || ($role === 'admin' && $status === 'active')) {
            return false;
        }

        return ! Membership::query()
            ->where('company_id', $membership->company_id)
            ->where('id', '!=', $membership->id)
            ->where('role', 'admin')
            ->where('status', 'active')
            ->exists();
    }
}
