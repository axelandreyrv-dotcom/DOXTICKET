<?php

namespace App\Contracts\Mail;

use App\Models\MailAccount;
use App\Support\Mail\FetchedMailMessage;

interface MailboxClient
{
    /**
     * @return iterable<FetchedMailMessage>
     */
    public function fetchNewMessages(MailAccount $account): iterable;
}
