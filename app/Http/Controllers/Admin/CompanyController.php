<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\MailAccount;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Admin\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::query()
            ->orderBy('name')
            ->paginate(25);

        $companyIds = $companies->getCollection()->pluck('id');

        $membershipCounts = Membership::query()
            ->whereIn('company_id', $companyIds)
            ->selectRaw('company_id, count(*) as total')
            ->groupBy('company_id')
            ->pluck('total', 'company_id');

        $ticketCounts = Ticket::withoutTenant()
            ->whereIn('company_id', $companyIds)
            ->selectRaw('company_id, count(*) as total')
            ->groupBy('company_id')
            ->pluck('total', 'company_id');

        $mailAccounts = MailAccount::withoutTenant()
            ->whereIn('company_id', $companyIds)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->get()
            ->keyBy('company_id');

        return view('admin.companies.index', [
            'companies' => $companies,
            'membershipCounts' => $membershipCounts,
            'ticketCounts' => $ticketCounts,
            'mailAccounts' => $mailAccounts,
        ]);
    }

    public function create(): View
    {
        return view('admin.companies.form', [
            'company' => new Company([
                'status' => 'active',
                'locale_default' => 'es',
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $company = Company::query()->create($this->validatedCompanyData($request));

        $auditLogger->record($request, 'admin.company.created', $company, [
            'slug' => $company->slug,
            'status' => $company->status,
        ]);

        return redirect()
            ->route('admin.companies.index')
            ->with('status', 'Empresa creada.');
    }

    public function edit(Company $company): View
    {
        return view('admin.companies.form', [
            'company' => $company,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Company $company, AuditLogger $auditLogger): RedirectResponse
    {
        $before = $company->only(['name', 'slug', 'country', 'phone', 'status', 'locale_default']);

        $company->update($this->validatedCompanyData($request, $company));

        $auditLogger->record($request, 'admin.company.updated', $company, [
            'before' => $before,
            'after' => $company->only(['name', 'slug', 'country', 'phone', 'status', 'locale_default']),
        ]);

        return redirect()
            ->route('admin.companies.index')
            ->with('status', 'Empresa actualizada.');
    }

    public function updateStatus(Request $request, Company $company, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'disabled', 'archived'])],
        ]);

        $previousStatus = $company->status;

        $company->update(['status' => $validated['status']]);

        $auditLogger->record($request, 'admin.company.status_changed', $company, [
            'from' => $previousStatus,
            'to' => $company->status,
        ]);

        return redirect()
            ->route('admin.companies.index')
            ->with('status', 'Estado de empresa actualizado.');
    }

    public function destroy(Request $request, Company $company, AuditLogger $auditLogger): RedirectResponse
    {
        $activeMembershipId = $request->session()->get('active_membership_id');

        if ($activeMembershipId !== null) {
            $activeCompanyId = Membership::query()
                ->whereKey((int) $activeMembershipId)
                ->value('company_id');

            if ((int) $activeCompanyId === $company->id) {
                $request->session()->forget('active_membership_id');
            }
        }

        User::query()
            ->where('last_active_company_id', $company->id)
            ->update(['last_active_company_id' => null]);

        $company->delete();

        $auditLogger->record($request, 'admin.company.deleted', $company, [
            'slug' => $company->slug,
            'status' => $company->status,
        ]);

        return redirect()
            ->route('admin.companies.index')
            ->with('status', 'Empresa eliminada.');
    }

    /**
     * @return array{name: string, slug: string, country: string|null, phone: string|null, status: string, locale_default: string}
     */
    private function validatedCompanyData(Request $request, ?Company $company = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('companies', 'slug')->ignore($company?->id),
            ],
            'country' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(['active', 'disabled', 'archived'])],
            'locale_default' => ['required', Rule::in(['es', 'en'])],
        ]);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'country' => isset($validated['country']) ? trim($validated['country']) : null,
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'locale_default' => $validated['locale_default'],
        ];
    }
}
