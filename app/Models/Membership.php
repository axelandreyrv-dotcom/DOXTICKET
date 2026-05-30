<?php

namespace App\Models;

use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'company_id',
    'user_id',
    'role',
    'status',
    'invited_by_user_id',
    'invited_at',
    'accepted_at',
    'last_selected_at',
    'preferences',
])]
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
            'last_selected_at' => 'datetime',
            'preferences' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Membership $membership): void {
            $membership->uuid ??= (string) Str::uuid();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
