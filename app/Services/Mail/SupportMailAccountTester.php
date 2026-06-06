<?php

namespace App\Services\Mail;

use App\Contracts\Mail\ImapConnection;
use App\Contracts\Mail\MailAccountTester;
use App\Models\MailAccount;
use App\Support\Mail\MailAccountTestResult;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SupportMailAccountTester implements MailAccountTester
{
    public function __construct(
        private readonly ImapConnection $imapConnection,
        private readonly TenantMailerConfigurator $mailerConfigurator,
    ) {}

    public function test(MailAccount $account): MailAccountTestResult
    {
        try {
            $this->testImap($account);
            $this->testSmtp($account);

            return MailAccountTestResult::ok();
        } catch (Throwable $exception) {
            return MailAccountTestResult::failed($this->safeErrorMessage($exception, $account));
        }
    }

    private function testImap(MailAccount $account): void
    {
        foreach ($this->imapConnection->fetchNewMessages($account) as $message) {
            break;
        }
    }

    private function testSmtp(MailAccount $account): void
    {
        $this->mailerConfigurator->configure($account);
        Mail::mailer('tenant_smtp')->getSymfonyTransport()->start();
    }

    private function safeErrorMessage(Throwable $exception, MailAccount $account): string
    {
        $message = Str::limit($exception->getMessage(), 500, '');
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
}
