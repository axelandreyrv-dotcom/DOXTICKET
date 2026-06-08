<?php

namespace App\Services\Admin;

use Illuminate\Mail\MailManager;

class GlobalMailConfiguration
{
    public function __construct(private readonly GlobalSmtpSettings $settings) {}

    public function apply(): void
    {
        $values = $this->settings->formValues();

        config([
            'mail.default' => $values['mail_mailer'],
            'mail.from.address' => $values['mail_from_address'] ?: config('mail.from.address'),
            'mail.from.name' => $values['mail_from_name'] ?: config('app.name', 'DoxTicket'),
        ]);

        if ($values['mail_mailer'] !== 'smtp') {
            app(MailManager::class)->purge('smtp');

            return;
        }

        $encryption = $values['mail_encryption'] === 'none' ? null : $values['mail_encryption'];

        config([
            'mail.mailers.smtp' => array_merge((array) config('mail.mailers.smtp', []), [
                'transport' => 'smtp',
                'scheme' => $values['mail_encryption'] === 'ssl' ? 'smtps' : null,
                'host' => $values['mail_host'],
                'port' => $values['mail_port'],
                'username' => $values['mail_username'],
                'password' => $this->settings->password(),
                'encryption' => $encryption,
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            ]),
        ]);

        app(MailManager::class)->purge('smtp');
    }
}
