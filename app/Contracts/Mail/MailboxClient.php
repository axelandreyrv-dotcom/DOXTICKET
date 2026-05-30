<?php

namespace App\Contracts\Mail;

use App\Models\MailAccount;

interface MailboxClient
{
    /**
     * @return iterable<\App\Support\Mail\FetchedMailMessage>
     */
    public function fetchNewMessages(MailAccount $account): iterable;
}
