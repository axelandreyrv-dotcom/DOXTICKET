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
    'author_user_id',
    'author_membership_id',
    'author_email',
    'author_name',
    'visibility',
    'direction',
    'body_html',
    'body_text',
    'external_images_blocked',
    'external_image_urls',
    'message_id_header',
    'in_reply_to_header',
    'references_header',
    'headers_raw',
    'delivered_at',
])]
class TicketMessage extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected function casts(): array
    {
        return [
            'external_images_blocked' => 'boolean',
            'external_image_urls' => 'array',
            'headers_raw' => 'array',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TicketMessage $message): void {
            $message->uuid ??= (string) Str::uuid();
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function authorMembership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'author_membership_id');
    }
}
