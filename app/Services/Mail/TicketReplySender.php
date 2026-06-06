<?php

namespace App\Services\Mail;

use App\Mail\Tickets\TicketReplyMail;
use App\Models\MailAccount;
use App\Models\Ticket;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TicketReplySender
{
    public function __construct(
        private readonly TenantMailerConfigurator $mailerConfigurator,
        private readonly OAuthTicketReplyApiClient $oauthClient,
    ) {}

    public function send(MailAccount $account, Ticket $ticket, string $bodyText, array $attachments = []): void
    {
        try {
            if ($account->usesOAuth()) {
                $this->oauthClient->sendTicketReply($account, $ticket, $bodyText, $attachments);

                return;
            }

            $this->mailerConfigurator->configure($account);

            Mail::mailer('tenant_smtp')
                ->to($ticket->requester_email, $ticket->requester_name)
                ->send(new TicketReplyMail($ticket, $account, $bodyText, $attachments));
        } catch (Throwable $exception) {
            $message = $this->safeErrorMessage($exception->getMessage(), $account);
            $account->forceFill(['last_error' => $message])->save();

            throw new RuntimeException($message, previous: $exception);
        }
    }

    private function safeErrorMessage(?string $message, MailAccount $account): string
    {
        $message = Str::limit($message ?: 'No se pudo enviar la respuesta.', 500, '');
        $secrets = array_filter([
            $account->password_encrypted,
            $account->oauth_access_token,
            $account->oauth_refresh_token,
            $account->username,
            config('doxticket.oauth.providers.'.$account->provider.'.client_secret'),
        ]);

        foreach ($secrets as $secret) {
            $message = str_replace((string) $secret, '[redacted]', $message);
        }

        return preg_replace('/(refresh_token|access_token|client_secret|password|token|secret|authorization)(=|:)\S+/i', '$1$2[redacted]', $message) ?? 'No se pudo enviar la respuesta.';
    }
}
