<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\MailAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'company_id',
    'provider',
    'from_name',
    'from_email',
    'host_imap',
    'port_imap',
    'security_imap',
    'host_smtp',
    'port_smtp',
    'security_smtp',
    'username',
    'password_encrypted',
    'oauth_access_token',
    'oauth_refresh_token',
    'oauth_expires_at',
    'folder_in',
    'auto_reply_enabled',
    'is_active',
    'last_uid',
    'last_sync_at',
    'last_error',
])]
class MailAccount extends Model
{
    /** @use HasFactory<MailAccountFactory> */
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'password_encrypted' => 'encrypted',
            'oauth_access_token' => 'encrypted',
            'oauth_refresh_token' => 'encrypted',
            'oauth_expires_at' => 'datetime',
            'auto_reply_enabled' => 'boolean',
            'is_active' => 'boolean',
            'last_sync_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MailAccount $account): void {
            $account->uuid ??= (string) Str::uuid();
            $account->provider ??= 'imap_smtp';
            $account->folder_in = trim((string) ($account->folder_in ?: 'INBOX')) ?: 'INBOX';
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
