<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'status',
    'destination',
    'started_at',
    'finished_at',
    'size_bytes',
    'error',
    'meta',
])]
class BackupRun extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BackupRun $backupRun): void {
            $backupRun->uuid ??= (string) Str::uuid();
        });
    }
}
