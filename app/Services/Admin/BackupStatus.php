<?php

namespace App\Services\Admin;

use App\Models\BackupRun;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class BackupStatus
{
    public function latestSuccessful(): ?BackupRun
    {
        return BackupRun::query()
            ->where('status', 'succeeded')
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->first();
    }

    public function latestRun(): ?BackupRun
    {
        return BackupRun::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return Collection<int, BackupRun>
     */
    public function recentRuns(int $limit = 5): Collection
    {
        return BackupRun::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function hasRecentSuccessfulBackup(int $maxAgeHours = 24): bool
    {
        $latest = $this->latestSuccessful();

        return $latest?->finished_at instanceof Carbon
            && $latest->finished_at->greaterThanOrEqualTo(now()->subHours($maxAgeHours));
    }

    public function rollbackAvailable(): bool
    {
        $latest = $this->latestSuccessful();

        return $latest !== null
            && (bool) ($latest->meta['rollback_available'] ?? false);
    }

    public function humanSize(?int $bytes): string
    {
        if ($bytes === null) {
            return 'Sin tamaño';
        }

        if ($bytes >= 1024 * 1024) {
            return (int) round($bytes / (1024 * 1024)).' MB';
        }

        if ($bytes >= 1024) {
            return (int) round($bytes / 1024).' KB';
        }

        return $bytes.' B';
    }
}
