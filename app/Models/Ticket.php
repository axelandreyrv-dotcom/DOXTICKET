<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'company_id',
    'mail_account_id',
    'category_id',
    'assigned_to_membership_id',
    'created_by_membership_id',
    'requester_email',
    'requester_name',
    'subject',
    'public_number',
    'public_key',
    'status',
    'priority',
    'source',
    'external_thread_id',
    'first_opened_at',
    'first_response_at',
    'resolved_at',
    'closed_at',
    'merged',
    'merged_into_ticket_id',
    'merged_at',
    'merged_by_membership_id',
    'sla_due_at',
    'last_activity_at',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    public const ACTIVE_STATUSES = ['new', 'open', 'in_progress', 'waiting_customer', 'waiting_internal', 'reopened'];

    protected function casts(): array
    {
        return [
            'first_opened_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'merged' => 'boolean',
            'merged_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            $ticket->uuid ??= (string) Str::uuid();
            $ticket->subject = trim((string) ($ticket->subject ?: 'Sin Asunto')) ?: 'Sin Asunto';
            $ticket->status ??= 'new';
            $ticket->priority ??= 'medium';
            $ticket->source ??= 'manual';
            $ticket->last_activity_at ??= now();

            if ($ticket->public_number === null) {
                $ticket->public_number = ((int) self::withoutTenant()
                    ->where('company_id', $ticket->company_id)
                    ->max('public_number')) + 1;
            }

            $ticket->public_key ??= 'DT-'.$ticket->public_number;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assignedToMembership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'assigned_to_membership_id');
    }

    public function createdByMembership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'created_by_membership_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }
}
