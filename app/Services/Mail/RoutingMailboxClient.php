<?php

namespace App\Services\Mail;

use App\Contracts\Mail\MailboxClient;
use App\Models\MailAccount;

class RoutingMailboxClient implements MailboxClient
{
    public function __construct(
        private readonly MailboxClient $imapClient,
        private readonly MailboxClient $oauthClient,
    ) {}

    public function fetchNewMessages(MailAccount $account): iterable
    {
        if ($account->usesOAuth()) {
            return $this->oauthClient->fetchNewMessages($account);
        }

        return $this->imapClient->fetchNewMessages($account);
    }
}
