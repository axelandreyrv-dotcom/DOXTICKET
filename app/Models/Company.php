<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'slug',
    'country',
    'phone',
    'status',
    'logo_path',
    'locale_default',
    'storage_limit_bytes',
    'storage_used_bytes',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            $company->uuid ??= (string) Str::uuid();
        });
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}
