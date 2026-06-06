<?php

namespace App\Services\Mail;

use App\Models\MailAccount;
use Illuminate\Mail\MailManager;

class TenantMailerConfigurator
{
    public function configure(MailAccount $mailAccount, string $mailer = 'tenant_smtp'): void
    {
        $security = $mailAccount->security_smtp === 'none' ? null : $mailAccount->security_smtp;

        config([
            'mail.mailers.'.$mailer => [
                'transport' => 'smtp',
                'host' => $mailAccount->host_smtp,
                'port' => $mailAccount->port_smtp,
                'username' => $mailAccount->username,
                'password' => $mailAccount->password_encrypted,
                'scheme' => $mailAccount->security_smtp === 'ssl' ? 'smtps' : null,
                'encryption' => $security,
                'timeout' => null,
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            ],
        ]);

        app(MailManager::class)->purge($mailer);
    }
}
