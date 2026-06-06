<?php

namespace Tests\Unit\Mail;

use App\Contracts\Mail\MailboxClient;
use App\Models\Company;
use App\Models\MailAccount;
use App\Services\Mail\RoutingMailboxClient;
use App\Support\Mail\FetchedMailMessage;
use App\Support\Mail\InboundMailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingMailboxClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_routes_oauth_accounts_to_oauth_client_and_password_accounts_to_imap_client(): void
    {
        $imapAccount = MailAccount::factory()->for(Company::factory())->create(['provider' => 'imap_smtp']);
        $gmailAccount = MailAccount::factory()->for(Company::factory())->create(['provider' => 'gmail']);
        $imap = new RecordingMailboxClient('imap');
        $oauth = new RecordingMailboxClient('oauth');
        $router = new RoutingMailboxClient($imap, $oauth);

        iterator_to_array($router->fetchNewMessages($imapAccount));
        iterator_to_array($router->fetchNewMessages($gmailAccount));

        $this->assertSame([$imapAccount->id], $imap->accountIds);
        $this->assertSame([$gmailAccount->id], $oauth->accountIds);
    }
}

class RecordingMailboxClient implements MailboxClient
{
    /** @var list<int> */
    public array $accountIds = [];

    public function __construct(private readonly string $uid) {}

    public function fetchNewMessages(MailAccount $account): iterable
    {
        $this->accountIds[] = $account->id;

        yield new FetchedMailMessage($this->uid, new InboundMailMessage(
            messageId: '<'.$this->uid.'@example.test>',
            fromEmail: 'requester@example.test',
            fromName: null,
            subject: 'Test',
            bodyText: 'Body',
            bodyHtml: null,
        ));
    }
}
