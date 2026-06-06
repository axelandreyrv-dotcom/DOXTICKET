<?php

namespace App\Contracts\Mail;

use App\Models\MailAccount;
use App\Support\Mail\RawImapMessage;

interface ImapConnection
{
    /**
     * @return iterable<RawImapMessage>
     */
    public function fetchNewMessages(MailAccount $account): iterable;
}
