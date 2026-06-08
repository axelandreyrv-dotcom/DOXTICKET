<?php

namespace Tests\Unit\Mail;

use App\Models\MailAccount;
use App\Services\Mail\NativeImapConnection;
use Illuminate\Support\Facades\Config;
use ReflectionMethod;
use Tests\TestCase;

class NativeImapConnectionTest extends TestCase
{
    public function test_mailbox_name_validates_certificates_by_default(): void
    {
        Config::set('doxticket.mail.imap_validate_cert', true);

        $this->assertSame(
            '{imap.gmail.com:993/imap/ssl}INBOX',
            $this->mailboxName()
        );
    }

    public function test_mailbox_name_can_disable_certificate_validation_for_local_qa(): void
    {
        Config::set('doxticket.mail.imap_validate_cert', false);

        $this->assertSame(
            '{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX',
            $this->mailboxName()
        );
    }

    public function test_search_criteria_uses_all_so_php_imap_returns_uids_compatibly(): void
    {
        $account = new MailAccount(['last_uid' => '11']);
        $method = new ReflectionMethod(NativeImapConnection::class, 'searchCriteria');
        $method->setAccessible(true);

        $this->assertSame('ALL', $method->invoke(new NativeImapConnection, $account));
    }

    public function test_uid_filter_skips_messages_already_processed(): void
    {
        $account = new MailAccount(['last_uid' => '11']);
        $method = new ReflectionMethod(NativeImapConnection::class, 'shouldFetchUid');
        $method->setAccessible(true);
        $connection = new NativeImapConnection;

        $this->assertFalse($method->invoke($connection, $account, 11));
        $this->assertTrue($method->invoke($connection, $account, 12));
    }

    private function mailboxName(): string
    {
        $account = new MailAccount([
            'host_imap' => 'imap.gmail.com',
            'port_imap' => 993,
            'security_imap' => 'ssl',
            'folder_in' => 'INBOX',
        ]);

        $method = new ReflectionMethod(NativeImapConnection::class, 'mailboxName');
        $method->setAccessible(true);

        return $method->invoke(new NativeImapConnection, $account);
    }
}
