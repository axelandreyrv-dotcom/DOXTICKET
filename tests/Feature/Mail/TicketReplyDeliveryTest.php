<?php

namespace Tests\Feature\Mail;

use App\Models\Company;
use App\Models\MailAccount;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Mail\OAuthTicketReplyApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TicketReplyDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_reply_uses_oauth_api_client_for_gmail_account(): void
    {
        [$user, $membership] = $this->tenantFixture();
        $account = MailAccount::factory()->for($membership->company)->create([
            'provider' => 'gmail',
            'from_name' => 'Mesa TI',
            'from_email' => 'soporte@example.test',
            'oauth_access_token' => 'gmail-access-token',
            'is_active' => true,
        ]);
        $ticket = Ticket::factory()->for($membership->company)->create([
            'requester_email' => 'ana@example.test',
            'requester_name' => 'Ana Mesa',
            'subject' => 'VPN oficina principal',
            'status' => 'open',
        ]);
        $client = new RecordingOAuthTicketReplyApiClient;
        $this->app->instance(OAuthTicketReplyApiClient::class, $client);

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->post(route('app.tickets.replies.store', $ticket->public_key), [
                'body_text' => 'Hola Ana, saliendo por Gmail API.',
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key))
            ->assertSessionHas('status', 'reply-sent');

        $this->assertSame([[
            'mail_account_id' => $account->id,
            'ticket_id' => $ticket->id,
            'body_text' => 'Hola Ana, saliendo por Gmail API.',
        ]], $client->sent);

        $this->assertDatabaseHas('ticket_messages', [
            'company_id' => $membership->company_id,
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'body_text' => 'Hola Ana, saliendo por Gmail API.',
        ]);
    }

    public function test_oauth_reply_failure_is_sanitized_and_does_not_store_outbound_message(): void
    {
        [$user, $membership] = $this->tenantFixture();
        $account = MailAccount::factory()->for($membership->company)->create([
            'provider' => 'gmail',
            'from_email' => 'soporte@example.test',
            'oauth_access_token' => 'secret-access-token',
            'is_active' => true,
        ]);
        $ticket = Ticket::factory()->for($membership->company)->create([
            'requester_email' => 'ana@example.test',
            'status' => 'open',
        ]);
        $this->app->instance(OAuthTicketReplyApiClient::class, new FailingOAuthTicketReplyApiClient(
            'gmail failed token=secret-access-token',
        ));

        $this->actingAs($user)
            ->withSession(['active_membership_id' => $membership->id])
            ->from(route('app.tickets.show', $ticket->public_key))
            ->post(route('app.tickets.replies.store', $ticket->public_key), [
                'body_text' => 'Esto no debe guardarse.',
            ])
            ->assertRedirect(route('app.tickets.show', $ticket->public_key))
            ->assertSessionHasErrors('mail_delivery');

        $account->refresh();

        $this->assertSame('gmail failed token=[redacted]', $account->last_error);
        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'body_text' => 'Esto no debe guardarse.',
        ]);
    }

    /**
     * @return array{User, Membership}
     */
    private function tenantFixture(): array
    {
        $user = User::factory()->create(['name' => 'QA Admin']);
        $company = Company::factory()->create(['name' => 'Dox IT']);
        $membership = Membership::factory()->for($user)->for($company)->create(['role' => 'agent', 'status' => 'active']);

        return [$user, $membership];
    }
}

class RecordingOAuthTicketReplyApiClient extends OAuthTicketReplyApiClient
{
    /** @var list<array{mail_account_id: int, ticket_id: int, body_text: string}> */
    public array $sent = [];

    public function sendTicketReply(MailAccount $account, Ticket $ticket, string $bodyText, array $attachments = []): void
    {
        $this->sent[] = [
            'mail_account_id' => $account->id,
            'ticket_id' => $ticket->id,
            'body_text' => $bodyText,
        ];
    }
}

class FailingOAuthTicketReplyApiClient extends OAuthTicketReplyApiClient
{
    public function __construct(private readonly string $message) {}

    public function sendTicketReply(MailAccount $account, Ticket $ticket, string $bodyText, array $attachments = []): void
    {
        throw new RuntimeException($this->message);
    }
}
