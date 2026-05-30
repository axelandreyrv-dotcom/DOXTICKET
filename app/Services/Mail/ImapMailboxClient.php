<?php

namespace App\Services\Mail;

use App\Contracts\Mail\MailboxClient;
use App\Models\MailAccount;
use RuntimeException;

class ImapMailboxClient implements MailboxClient
{
    public function fetchNewMessages(MailAccount $account): iterable
    {
        throw new RuntimeException('IMAP client adapter is not configured yet.');
    }
}
