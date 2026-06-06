<?php

namespace Tests\Unit\Mail;

use App\Contracts\Mail\ImapConnection;
use App\Models\Company;
use App\Models\MailAccount;
use App\Services\Mail\ImapMailboxClient;
use App\Support\Mail\FetchedMailMessage;
use App\Support\Mail\RawImapAttachment;
use App\Support\Mail\RawImapMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImapMailboxClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_new_messages_normalizes_headers_sender_subject_and_bodies(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create();
        $client = new ImapMailboxClient(new FakeImapConnection([
            new RawImapMessage(
                uid: '42',
                headers: implode("\r\n", [
                    'Message-ID: <msg-42@example.test>',
                    'From: "Mesa Operaciones" <requester@example.test>',
                    'Subject: =?UTF-8?B?VlBOIGPDrWRh?=',
                    'In-Reply-To: <previous@example.test>',
                    'References: <root@example.test> <previous@example.test>',
                    'Date: Sat, 30 May 2026 10:15:00 -0600',
                    'X-Custom: visible',
                ]),
                bodyText: "No conecta.\n",
                bodyHtml: '<p>No conecta.</p>',
            ),
        ]));

        $messages = iterator_to_array($client->fetchNewMessages($account));

        $this->assertCount(1, $messages);
        $this->assertContainsOnlyInstancesOf(FetchedMailMessage::class, $messages);
        $this->assertSame('42', $messages[0]->uid);
        $this->assertSame('<msg-42@example.test>', $messages[0]->message->messageId);
        $this->assertSame('requester@example.test', $messages[0]->message->fromEmail);
        $this->assertSame('Mesa Operaciones', $messages[0]->message->fromName);
        $this->assertSame('VPN cída', $messages[0]->message->subject);
        $this->assertSame("No conecta.\n", $messages[0]->message->bodyText);
        $this->assertSame('<p>No conecta.</p>', $messages[0]->message->bodyHtml);
        $this->assertSame('<previous@example.test>', $messages[0]->message->inReplyTo);
        $this->assertSame('<root@example.test> <previous@example.test>', $messages[0]->message->references);
        $this->assertSame('visible', $messages[0]->message->headers['X-Custom']);
        $this->assertSame('2026-05-30 10:15:00', $messages[0]->message->deliveredAt?->setTimezone('America/Costa_Rica')->format('Y-m-d H:i:s'));
    }

    public function test_fetch_new_messages_skips_messages_without_valid_sender(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create();
        $client = new ImapMailboxClient(new FakeImapConnection([
            new RawImapMessage(
                uid: '51',
                headers: "Message-ID: <msg-51@example.test>\r\nSubject: Sin remitente",
                bodyText: 'No debe entrar.',
                bodyHtml: null,
            ),
        ]));

        $this->assertSame([], iterator_to_array($client->fetchNewMessages($account)));
    }

    public function test_fetch_new_messages_maps_raw_attachments(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create();
        $client = new ImapMailboxClient(new FakeImapConnection([
            new RawImapMessage(
                uid: '61',
                headers: "Message-ID: <msg-61@example.test>\r\nFrom: requester@example.test\r\nSubject: Evidencia",
                bodyText: 'Adjunto evidencia.',
                bodyHtml: null,
                attachments: [
                    new RawImapAttachment(
                        filename: 'captura.png',
                        mimeType: 'image/png',
                        contents: 'png-bytes',
                    ),
                ],
            ),
        ]));

        $messages = iterator_to_array($client->fetchNewMessages($account));

        $this->assertCount(1, $messages[0]->message->attachments);
        $this->assertSame('captura.png', $messages[0]->message->attachments[0]->filename);
        $this->assertSame('image/png', $messages[0]->message->attachments[0]->mimeType);
        $this->assertSame('png-bytes', $messages[0]->message->attachments[0]->contents);
    }

    public function test_fetch_new_messages_decodes_mime_encoded_attachment_filenames(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create();
        $client = new ImapMailboxClient(new FakeImapConnection([
            new RawImapMessage(
                uid: '62',
                headers: "Message-ID: <msg-62@example.test>\r\nFrom: requester@example.test\r\nSubject: Evidencia",
                bodyText: 'Adjunto evidencia.',
                bodyHtml: null,
                attachments: [
                    new RawImapAttachment(
                        filename: '=?UTF-8?B?Y2FwdHVyYSByZWQucG5n?=',
                        mimeType: 'image/png',
                        contents: 'png-bytes',
                    ),
                ],
            ),
        ]));

        $messages = iterator_to_array($client->fetchNewMessages($account));

        $this->assertSame('captura red.png', $messages[0]->message->attachments[0]->filename);
    }
}

class FakeImapConnection implements ImapConnection
{
    /**
     * @param  list<RawImapMessage>  $messages
     */
    public function __construct(private readonly array $messages) {}

    public function fetchNewMessages(MailAccount $account): iterable
    {
        return $this->messages;
    }
}
