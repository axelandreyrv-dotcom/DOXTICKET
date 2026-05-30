<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setup\StoreSetupRequest;
use App\Models\Company;
use App\Models\Membership;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function create(): RedirectResponse|View
    {
        if ($this->isCompleted()) {
            return redirect('/login');
        }

        return view('setup.placeholder');
    }

    public function store(StoreSetupRequest $request): RedirectResponse
    {
        abort_if($this->isCompleted(), 403);

        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $company = Company::query()->create([
                'name' => $data['company_name'],
                'slug' => $this->uniqueCompanySlug($data['company_name']),
                'status' => 'active',
                'locale_default' => $data['locale'],
            ]);

            $user = User::query()->create([
                'name' => $data['admin_name'],
                'email' => Str::lower($data['admin_email']),
                'password' => $data['admin_password'],
                'is_superadmin' => true,
                'is_active' => true,
                'locale' => $data['locale'],
                'last_active_company_id' => $company->id,
            ]);

            Membership::query()->create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'status' => 'active',
                'accepted_at' => now(),
                'preferences' => [],
            ]);

            SystemSetting::put('setup.completed', true);
            SystemSetting::put('app.locale_default', $data['locale']);
            SystemSetting::put('telemetry.enabled', (bool) ($data['telemetry_enabled'] ?? false));
        });

        return redirect('/login')->with('status', 'setup-completed');
    }

    private function isCompleted(): bool
    {
        return SystemSetting::get('setup.completed', false) === true;
    }

    private function uniqueCompanySlug(string $name): string
    {
        $base = Str::slug($name) ?: 'empresa';
        $slug = Str::limit($base, 70, '');
        $candidate = $slug;
        $counter = 2;

        while (Company::query()->where('slug', $candidate)->exists()) {
            $candidate = Str::limit($slug, 68, '').'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
