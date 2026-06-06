<?php

namespace App\Jobs\Mail;

use App\Contracts\Mail\MailboxClient;
use App\Models\MailAccount;
use App\Services\Mail\InboundMailProcessor;
use App\Support\Mail\FetchedMailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as QueueableJob;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Str;
use Throwable;

class IngestMailboxJob implements ShouldQueue
{
    use QueueableJob;

    public function __construct(public readonly int $mailAccountId)
    {
        $this->onQueue('mail');
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('mail-account-'.$this->mailAccountId))->releaseAfter(60),
        ];
    }

    public function handle(MailboxClient $mailbox, InboundMailProcessor $processor): void
    {
        $account = MailAccount::withoutTenant()->whereKey($this->mailAccountId)->first();

        if ($account === null || ! $account->is_active) {
            return;
        }

        try {
            foreach ($mailbox->fetchNewMessages($account) as $fetched) {
                if (! $fetched instanceof FetchedMailMessage) {
                    continue;
                }

                $processor->process($account, $fetched->message);
                $account->forceFill(['last_uid' => $this->latestUid($account->last_uid, $fetched->uid)])->save();
            }

            $account->forceFill([
                'last_error' => null,
                'last_sync_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $account->forceFill([
                'last_error' => $this->safeErrorMessage($exception, $account),
                'last_sync_at' => now(),
            ])->save();
        }
    }

    private function latestUid(?string $currentUid, string $fetchedUid): string
    {
        if (ctype_digit((string) $currentUid) && ctype_digit($fetchedUid)) {
            return (string) max((int) $currentUid, (int) $fetchedUid);
        }

        return $fetchedUid;
    }

    private function safeErrorMessage(Throwable $exception, MailAccount $account): string
    {
        $message = Str::limit($exception->getMessage(), 500, '');
        $secrets = array_filter([
            $account->password_encrypted,
            $account->oauth_access_token,
            $account->oauth_refresh_token,
            $account->username,
        ]);

        foreach ($secrets as $secret) {
            $message = str_replace((string) $secret, '[redacted]', $message);
        }

        return preg_replace('/(password|token|secret|authorization)(=|:)\S+/i', '$1$2[redacted]', $message) ?? 'Mail ingestion failed.';
    }
}
