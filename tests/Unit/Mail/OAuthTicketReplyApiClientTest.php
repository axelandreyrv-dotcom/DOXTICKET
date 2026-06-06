<?php

namespace Tests\Unit\Mail;

use App\Models\Company;
use App\Models\MailAccount;
use App\Models\Ticket;
use App\Services\Mail\OAuthTicketReplyApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OAuthTicketReplyApiClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_gmail_reply_posts_base64url_raw_message_to_gmail_api(): void
    {
        $company = Company::factory()->create();
        $account = MailAccount::factory()->for($company)->create([
            'provider' => 'gmail',
            'from_name' => 'Mesa TI',
            'from_email' => 'soporte@example.test',
            'oauth_access_token' => 'gmail-access-token',
        ]);
        $ticket = Ticket::factory()->for($company)->create([
            'public_key' => 'DT-123',
            'requester_email' => 'ana@example.test',
            'requester_name' => 'Ana Mesa',
            'subject' => 'VPN oficina principal',
        ]);

        Http::fake([
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response(['id' => 'gmail-message-id']),
        ]);

        app(OAuthTicketReplyApiClient::class)->sendTicketReply($account, $ticket, 'Respuesta desde Gmail API.');

        Http::assertSent(function ($request): bool {
            $raw = $request['raw'] ?? '';
            $decoded = base64_decode(strtr($raw, '-_', '+/'));

            return $request->url() === 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send'
                && $request->hasHeader('Authorization', 'Bearer gmail-access-token')
                && str_contains($decoded, 'From: "Mesa TI" <soporte@example.test>')
                && str_contains($decoded, 'To: "Ana Mesa" <ana@example.test>')
                && str_contains($decoded, 'Reply-To: "Mesa TI" <soporte@example.test>')
                && str_contains($decoded, 'Subject: [DT-123] VPN oficina principal')
                && str_contains($decoded, 'Respuesta desde Gmail API.');
        });
    }

    public function test_microsoft_reply_posts_send_mail_json_to_graph_api(): void
    {
        $company = Company::factory()->create();
        $account = MailAccount::factory()->for($company)->create([
            'provider' => 'microsoft365',
            'from_name' => 'Mesa TI',
            'from_email' => 'soporte@example.test',
            'oauth_access_token' => 'graph-access-token',
        ]);
        $ticket = Ticket::factory()->for($company)->create([
            'public_key' => 'DT-456',
            'requester_email' => 'ana@example.test',
            'requester_name' => 'Ana Mesa',
            'subject' => 'Laptop sin red',
        ]);

        Http::fake([
            'https://graph.microsoft.com/v1.0/me/sendMail' => Http::response('', 202),
        ]);

        app(OAuthTicketReplyApiClient::class)->sendTicketReply($account, $ticket, 'Respuesta desde Graph.');

        Http::assertSent(fn ($request) => $request->url() === 'https://graph.microsoft.com/v1.0/me/sendMail'
            && $request->hasHeader('Authorization', 'Bearer graph-access-token')
            && $request['saveToSentItems'] === true
            && $request['message']['subject'] === '[DT-456] Laptop sin red'
            && $request['message']['body']['contentType'] === 'Text'
            && $request['message']['body']['content'] === 'Respuesta desde Graph.'
            && $request['message']['toRecipients'][0]['emailAddress']['address'] === 'ana@example.test'
            && $request['message']['replyTo'][0]['emailAddress']['address'] === 'soporte@example.test');
    }
}
