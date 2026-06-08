<?php

namespace App\Http\Controllers\App;

use App\Contracts\Mail\MailAccountTester;
use App\Contracts\Mail\OAuthTokenClient;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mail\StoreMailAccountRequest;
use App\Models\MailAccount;
use App\Services\Auth\Totp;
use App\Services\Mail\OAuthAuthorizationUrlFactory;
use App\Services\Mail\OAuthStateStore;
use App\Services\Mail\OAuthTokenStore;
use App\Support\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $totp = app(Totp::class);

        return view('app.settings.index', [
            'company' => app(TenantContext::class)->company(),
            'mailAccount' => MailAccount::query()->first(),
            'twoFactorProvisioningUri' => filled($user?->two_factor_secret) && ! $user?->hasTwoFactorEnabled()
                ? $totp->provisioningUri((string) $user->email, (string) $user->two_factor_secret)
                : null,
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

    public function testMail(MailAccountTester $tester): RedirectResponse
    {
        $account = MailAccount::query()->first();

        if ($account === null) {
            return redirect('/app/settings')
                ->withErrors(['mail_account' => 'Guarda una cuenta de correo antes de probar la conexión.']);
        }

        $result = $tester->test($account);

        if (! $result->ok) {
            $message = $this->safeTestMessage($result->message, $account);
            $account->forceFill(['last_error' => $message])->save();

            return redirect('/app/settings')
                ->withErrors(['mail_test' => $message]);
        }

        $account->forceFill(['last_error' => null])->save();

        return redirect('/app/settings')->with('status', 'mail-test-ok');
    }

    public function redirectOAuth(
        string $provider,
        OAuthStateStore $stateStore,
        OAuthAuthorizationUrlFactory $urlFactory,
    ): RedirectResponse {
        abort_unless(in_array($provider, ['gmail', 'microsoft365'], true), 404);

        $config = config("doxticket.oauth.providers.{$provider}");

        if (! is_array($config) || blank($config['client_id'] ?? null) || blank($config['redirect_uri'] ?? null)) {
            return redirect('/app/settings')
                ->withErrors(['oauth' => 'Configura las credenciales OAuth del proveedor antes de conectar la cuenta.']);
        }

        $company = app(TenantContext::class)->company();
        $state = $stateStore->create($provider, (int) $company?->id);

        return redirect()->away($urlFactory->make($provider, $state));
    }

    public function callbackOAuth(
        string $provider,
        OAuthStateStore $stateStore,
        OAuthTokenClient $tokenClient,
        OAuthTokenStore $tokenStore,
    ): RedirectResponse {
        abort_unless(in_array($provider, ['gmail', 'microsoft365'], true), 404);

        $company = app(TenantContext::class)->company();
        $state = (string) request('state', '');
        $code = (string) request('code', '');

        if ($code === '' || $stateStore->consume($state, $provider, (int) $company?->id) === null) {
            return redirect('/app/settings')
                ->withErrors(['oauth' => 'No se pudo validar la autorización OAuth. Intenta conectar la cuenta de nuevo.']);
        }

        $account = MailAccount::query()
            ->where('provider', $provider)
            ->first();

        if ($account === null) {
            return redirect('/app/settings')
                ->withErrors(['oauth' => 'Crea o selecciona una cuenta OAuth antes de completar la conexión.']);
        }

        try {
            $tokens = $tokenClient->exchange($provider, $code);
            $tokenStore->store($account, $tokens);
        } catch (Throwable $exception) {
            $message = $this->safeOAuthMessage($exception->getMessage(), $account);
            $account->forceFill(['last_error' => $message])->save();

            return redirect('/app/settings')
                ->withErrors(['oauth' => $message]);
        }

        return redirect('/app/settings')->with('status', 'mail-oauth-connected');
    }

    private function safeTestMessage(?string $message, MailAccount $account): string
    {
        $message = Str::limit($message ?: 'No se pudo probar la cuenta de correo.', 500, '');
        $secrets = array_filter([
            $account->password_encrypted,
            $account->oauth_access_token,
            $account->oauth_refresh_token,
            $account->username,
        ]);

        foreach ($secrets as $secret) {
            $message = str_replace((string) $secret, '[redacted]', $message);
        }

        return preg_replace('/(password|token|secret|authorization)(=|:)\S+/i', '$1$2[redacted]', $message) ?? 'No se pudo probar la cuenta de correo.';
    }

    private function safeOAuthMessage(?string $message, MailAccount $account): string
    {
        $message = Str::limit($message ?: 'No se pudo completar la conexión OAuth.', 500, '');
        $secrets = array_filter([
            $account->oauth_access_token,
            $account->oauth_refresh_token,
            config('doxticket.oauth.providers.'.$account->provider.'.client_secret'),
        ]);

        foreach ($secrets as $secret) {
            $message = str_replace((string) $secret, '[redacted]', $message);
        }

        return preg_replace('/(password|token|secret|authorization|client_secret)(=|:)\S+/i', '$1$2[redacted]', $message) ?? 'No se pudo completar la conexión OAuth.';
    }
}
