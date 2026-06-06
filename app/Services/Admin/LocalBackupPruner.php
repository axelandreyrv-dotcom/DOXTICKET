<?php

namespace App\Services\Admin;

use App\Models\BackupRun;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class LocalBackupPruner
{
    public function __construct(
        private readonly BackupPolicySettings $backupPolicy,
    ) {}

    /**
     * @return array{pruned: int}
     */
    public function prune(): array
    {
        $cutoff = now()->subDays($this->backupPolicy->retentionDays());
        $pruned = 0;

        BackupRun::query()
            ->where('status', 'succeeded')
            ->where('destination', 'local')
            ->whereNotNull('finished_at')
            ->where('finished_at', '<', $cutoff)
            ->orderBy('id')
            ->each(function (BackupRun $backupRun) use (&$pruned): void {
                $this->deleteArtifacts($backupRun);
                $this->markPruned($backupRun);
                $pruned++;
            });

        return ['pruned' => $pruned];
    }

    private function deleteArtifacts(BackupRun $backupRun): void
    {
        $meta = $backupRun->meta ?? [];
        $paths = array_values(array_filter([
            $meta['database_path'] ?? null,
            $meta['manifest_path'] ?? null,
        ], fn (mixed $path): bool => is_string($path) && str_starts_with($path, 'backups/')));

        if ($paths !== []) {
            Storage::disk('private')->delete($paths);
        }

        Storage::disk('private')->deleteDirectory("backups/{$backupRun->uuid}");
    }

    private function markPruned(BackupRun $backupRun): void
    {
        $meta = $backupRun->meta ?? [];
        Arr::forget($meta, ['database_path', 'manifest_path']);

        $backupRun->forceFill([
            'status' => 'pruned',
            'meta' => array_merge($meta, [
                'artifact_pruned' => true,
                'artifact_pruned_at' => now()->toISOString(),
                'rollback_available' => false,
            ]),
        ])->save();
    }
}
