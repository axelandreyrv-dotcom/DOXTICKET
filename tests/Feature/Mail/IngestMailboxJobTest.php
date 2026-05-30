<?php

namespace Tests\Feature\Mail;

use App\Contracts\Mail\MailboxClient;
use App\Jobs\Mail\IngestMailboxJob;
use App\Models\Company;
use App\Models\MailAccount;
use App\Models\Ticket;
use App\Support\Mail\FetchedMailMessage;
use App\Support\Mail\InboundMailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class IngestMailboxJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_fetches_messages_processes_them_and_advances_last_uid(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'last_uid' => '10',
            'last_error' => 'previous error',
        ]);

        $this->app->instance(MailboxClient::class, new FakeMailboxClient([
            new FetchedMailMessage('11', new InboundMailMessage(
                messageId: '<mail-11@example.test>',
                fromEmail: 'requester@example.test',
                fromName: 'Mesa',
                subject: 'VPN lenta',
                bodyText: 'La VPN esta lenta.',
                bodyHtml: null,
                headers: ['Message-Id' => '<mail-11@example.test>'],
                deliveredAt: now(),
            )),
            new FetchedMailMessage('12', new InboundMailMessage(
                messageId: '<mail-12@example.test>',
                fromEmail: 'requester@example.test',
                fromName: 'Mesa',
                subject: 'Monitor',
                bodyText: 'Sin imagen.',
                bodyHtml: null,
                headers: ['Message-Id' => '<mail-12@example.test>'],
                deliveredAt: now(),
            )),
        ]));

        IngestMailboxJob::dispatchSync($account->id);

        $account->refresh();

        $this->assertSame(2, Ticket::withoutTenant()->where('company_id', $account->company_id)->count());
        $this->assertSame('12', $account->last_uid);
        $this->assertNull($account->last_error);
        $this->assertNotNull($account->last_sync_at);
    }

    public function test_job_records_sanitized_error_without_leaking_password(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'password_encrypted' => 'top-secret-mail-password',
        ]);

        $this->app->instance(MailboxClient::class, new FailingMailboxClient(
            'Authentication failed for top-secret-mail-password',
        ));

        IngestMailboxJob::dispatchSync($account->id);

        $account->refresh();

        $this->assertStringContainsString('Authentication failed for [redacted]', (string) $account->last_error);
        $this->assertStringNotContainsString('top-secret-mail-password', (string) $account->last_error);
        $this->assertSame(0, Ticket::withoutTenant()->count());
    }

    public function test_job_skips_inactive_mail_account(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create(['is_active' => false]);
        $client = new FakeMailboxClient([
            new FetchedMailMessage('1', new InboundMailMessage(
                messageId: '<mail-1@example.test>',
                fromEmail: 'requester@example.test',
                fromName: null,
                subject: 'No debe procesar',
                bodyText: 'Inactive',
                bodyHtml: null,
            )),
        ]);
        $this->app->instance(MailboxClient::class, $client);

        IngestMailboxJob::dispatchSync($account->id);

        $this->assertFalse($client->wasCalled);
        $this->assertSame(0, Ticket::withoutTenant()->count());
    }
}

class FakeMailboxClient implements MailboxClient
{
    public bool $wasCalled = false;

    /**
     * @param list<FetchedMailMessage> $messages
     */
    public function __construct(private readonly array $messages) {}

    public function fetchNewMessages(MailAccount $account): iterable
    {
        $this->wasCalled = true;

        return $this->messages;
    }
}

class FailingMailboxClient implements MailboxClient
{
    public function __construct(private readonly string $message) {}

    public function fetchNewMessages(MailAccount $account): iterable
    {
        throw new RuntimeException($this->message);
    }
}
