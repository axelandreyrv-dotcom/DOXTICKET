<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mail\StoreMailAccountRequest;
use App\Models\MailAccount;
use App\Support\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('app.settings.index', [
            'company' => app(TenantContext::class)->company(),
            'mailAccount' => MailAccount::query()->first(),
        ]);
    }

    public function storeMail(StoreMailAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $password = $data['password'] ?? null;
        unset($data['password']);

        $data['auto_reply_enabled'] = $request->boolean('auto_reply_enabled');
        $data['is_active'] = true;
        $data['folder_in'] = trim((string) $data['folder_in']) ?: 'INBOX';

        $account = MailAccount::query()->first();

        if (filled($password)) {
            $data['password_encrypted'] = $password;
        }

        if ($account === null) {
            MailAccount::query()->create($data);
        } else {
            $account->update($data);
        }

        return redirect('/app/settings')->with('status', 'mail-settings-saved');
    }
}
