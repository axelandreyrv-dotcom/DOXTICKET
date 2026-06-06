<?php

namespace App\Contracts\Mail;

use App\Models\MailAccount;
use App\Support\Mail\MailAccountTestResult;

interface MailAccountTester
{
    public function test(MailAccount $account): MailAccountTestResult;
}
