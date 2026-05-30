<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'company_id',
    'ticket_id',
    'ticket_message_id',
    'filename',
    'mime_type',
    'size_bytes',
    'disk',
    'path',
    'checksum_sha256',
    'blocked_reason',
])]
class Attachment extends Model
{
    use BelongsToCompany, SoftDeletes;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::creating(function (Attachment $attachment): void {
            $attachment->uuid ??= (string) Str::uuid();
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
