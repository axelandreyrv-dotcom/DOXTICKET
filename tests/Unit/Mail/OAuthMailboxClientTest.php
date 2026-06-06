<?php

namespace Tests\Unit\Mail;

use App\Models\Company;
use App\Models\MailAccount;
use App\Services\Mail\OAuthMailboxClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OAuthMailboxClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_gmail_fetches_new_message_ids_and_normalizes_full_messages_oldest_first(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'gmail',
            'oauth_access_token' => 'gmail-access-token',
            'last_uid' => 'gmail-old',
        ]);

        Http::fake([
            'https://gmail.googleapis.com/gmail/v1/users/me/messages?*' => Http::response([
                'messages' => [
                    ['id' => 'gmail-newest'],
                    ['id' => 'gmail-older'],
                    ['id' => 'gmail-old'],
                ],
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/gmail-older?*' => Http::response($this->gmailMessage('gmail-older', 'older@example.test', 'Older message')),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/gmail-newest?*' => Http::response($this->gmailMessage('gmail-newest', 'newest@example.test', 'Newest message')),
        ]);

        $messages = iterator_to_array(app(OAuthMailboxClient::class)->fetchNewMessages($account));

        $this->assertCount(2, $messages);
        $this->assertSame('gmail-older', $messages[0]->uid);
        $this->assertSame('gmail-newest', $messages[1]->uid);
        $this->assertSame('<gmail-older@example.test>', $messages[0]->message->messageId);
        $this->assertSame('older@example.test', $messages[0]->message->fromEmail);
        $this->assertSame('Older message', $messages[0]->message->subject);
        $this->assertSame('Texto Gmail', $messages[0]->message->bodyText);
        $this->assertSame('<p>HTML Gmail</p>', $messages[0]->message->bodyHtml);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer gmail-access-token'));
    }

    public function test_microsoft_fetches_messages_and_normalizes_graph_payload_oldest_first(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'microsoft365',
            'oauth_access_token' => 'graph-access-token',
            'last_uid' => 'graph-old',
        ]);

        Http::fake([
            'https://graph.microsoft.com/v1.0/me/mailFolders/Inbox/messages?*' => Http::response([
                'value' => [
                    $this->graphMessage('graph-newest', 'newest@example.test', 'Newest Graph'),
                    $this->graphMessage('graph-older', 'older@example.test', 'Older Graph'),
                    $this->graphMessage('graph-old', 'old@example.test', 'Old Graph'),
                ],
            ]),
        ]);

        $messages = iterator_to_array(app(OAuthMailboxClient::class)->fetchNewMessages($account));

        $this->assertCount(2, $messages);
        $this->assertSame('graph-older', $messages[0]->uid);
        $this->assertSame('graph-newest', $messages[1]->uid);
        $this->assertSame('<graph-older@example.test>', $messages[0]->message->messageId);
        $this->assertSame('older@example.test', $messages[0]->message->fromEmail);
        $this->assertSame('Older Graph', $messages[0]->message->subject);
        $this->assertSame('Texto Graph', $messages[0]->message->bodyText);
        $this->assertSame('2026-05-31 11:00:00', $messages[0]->message->deliveredAt?->format('Y-m-d H:i:s'));

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer graph-access-token'));
    }

    public function test_gmail_downloads_attachment_parts_into_inbound_attachments(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'gmail',
            'oauth_access_token' => 'gmail-access-token',
        ]);
        $message = $this->gmailMessage('gmail-with-attachment', 'requester@example.test', 'Adjunto Gmail');
        $message['payload']['parts'][] = [
            'filename' => 'captura.png',
            'mimeType' => 'image/png',
            'body' => [
                'attachmentId' => 'att-1',
            ],
        ];

        Http::fake([
            'https://gmail.googleapis.com/gmail/v1/users/me/messages?*' => Http::response([
                'messages' => [['id' => 'gmail-with-attachment']],
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/gmail-with-attachment?*' => Http::response($message),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/gmail-with-attachment/attachments/att-1' => Http::response([
                'data' => rtrim(strtr(base64_encode('png-bytes'), '+/', '-_'), '='),
            ]),
        ]);

        $messages = iterator_to_array(app(OAuthMailboxClient::class)->fetchNewMessages($account));

        $this->assertCount(1, $messages[0]->message->attachments);
        $this->assertSame('captura.png', $messages[0]->message->attachments[0]->filename);
        $this->assertSame('image/png', $messages[0]->message->attachments[0]->mimeType);
        $this->assertSame('png-bytes', $messages[0]->message->attachments[0]->contents);
    }

    public function test_microsoft_downloads_file_attachments_into_inbound_attachments(): void
    {
        $account = MailAccount::factory()->for(Company::factory())->create([
            'provider' => 'microsoft365',
            'oauth_access_token' => 'graph-access-token',
        ]);
        $message = $this->graphMessage('graph-with-attachment', 'requester@example.test', 'Adjunto Graph');
        $message['hasAttachments'] = true;

        Http::fake([
            'https://graph.microsoft.com/v1.0/me/mailFolders/Inbox/messages?*' => Http::response([
                'value' => [$message],
            ]),
            'https://graph.microsoft.com/v1.0/me/messages/graph-with-attachment/attachments?*' => Http::response([
                'value' => [[
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'name' => 'evidencia.pdf',
                    'contentType' => 'application/pdf',
                    'contentBytes' => base64_encode('pdf-bytes'),
                ]],
            ]),
        ]);

        $messages = iterator_to_array(app(OAuthMailboxClient::class)->fetchNewMessages($account));

        $this->assertCount(1, $messages[0]->message->attachments);
        $this->assertSame('evidencia.pdf', $messages[0]->message->attachments[0]->filename);
        $this->assertSame('application/pdf', $messages[0]->message->attachments[0]->mimeType);
        $this->assertSame('pdf-bytes', $messages[0]->message->attachments[0]->contents);
    }

    /**
     * @return array<string, mixed>
     */
    private function gmailMessage(string $id, string $from, string $subject): array
    {
        return [
            'id' => $id,
            'payload' => [
                'headers' => [
                    ['name' => 'Message-ID', 'value' => '<'.$id.'@example.test>'],
                    ['name' => 'From', 'value' => '"Mesa" <'.$from.'>'],
                    ['name' => 'Subject', 'value' => $subject],
                    ['name' => 'Date', 'value' => 'Sun, 31 May 2026 05:00:00 -0600'],
                ],
                'parts' => [
                    [
                        'mimeType' => 'text/plain',
                        'body' => ['data' => rtrim(strtr(base64_encode('Texto Gmail'), '+/', '-_'), '=')],
                    ],
                    [
                        'mimeType' => 'text/html',
                        'body' => ['data' => rtrim(strtr(base64_encode('<p>HTML Gmail</p>'), '+/', '-_'), '=')],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function graphMessage(string $id, string $from, string $subject): array
    {
        return [
            'id' => $id,
            'internetMessageId' => '<'.$id.'@example.test>',
            'subject' => $subject,
            'receivedDateTime' => '2026-05-31T11:00:00Z',
            'from' => [
                'emailAddress' => [
                    'name' => 'Mesa',
                    'address' => $from,
                ],
            ],
            'body' => [
                'contentType' => 'text',
                'content' => 'Texto Graph',
            ],
            'internetMessageHeaders' => [
                ['name' => 'In-Reply-To', 'value' => '<previous@example.test>'],
                ['name' => 'References', 'value' => '<root@example.test> <previous@example.test>'],
            ],
        ];
    }
}
