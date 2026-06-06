<?php

namespace App\Services\Admin;

use App\Models\BackupRun;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class LocalBackupRunner
{
    public function run(string $trigger = 'manual'): BackupRun
    {
        $backupRun = BackupRun::query()->create([
            'status' => 'running',
            'destination' => 'local',
            'started_at' => now(),
            'meta' => [
                'trigger' => $trigger,
                'version' => config('doxticket.version', 'dev'),
            ],
        ]);

        try {
            $artifact = $this->writeDatabaseArtifact($backupRun);
            $manifestPath = $this->writeManifest($backupRun, $artifact, $trigger);

            $backupRun->forceFill([
                'status' => 'succeeded',
                'finished_at' => now(),
                'size_bytes' => $artifact['size_bytes'] + $artifact['manifest_size_bytes'],
                'error' => null,
                'meta' => [
                    'trigger' => $trigger,
                    'version' => config('doxticket.version', 'dev'),
                    'database_driver' => $artifact['database_driver'],
                    'database_path' => $artifact['database_path'],
                    'manifest_path' => $manifestPath,
                    'rollback_available' => true,
                ],
            ])->save();
        } catch (Throwable $exception) {
            $backupRun->forceFill([
                'status' => 'failed',
                'finished_at' => now(),
                'error' => $this->sanitizeError($exception->getMessage()),
                'meta' => array_merge($backupRun->meta ?? [], [
                    'rollback_available' => false,
                ]),
            ])->save();
        }

        return $backupRun->refresh();
    }

    /**
     * @return array{database_driver: string, database_path: string, size_bytes: int, manifest_size_bytes: int}
     */
    private function writeDatabaseArtifact(BackupRun $backupRun): array
    {
        $connection = (string) config('database.default');
        $database = config("database.connections.{$connection}");
        $driver = (string) ($database['driver'] ?? '');
        $directory = "backups/{$backupRun->uuid}";

        return match ($driver) {
            'sqlite' => $this->writeSqliteArtifact($directory, $database),
            'pgsql' => $this->writePostgresArtifact($directory, $database),
            default => throw new RuntimeException('El motor de base de datos no tiene backup local soportado.'),
        };
    }

    /**
     * @param  array<string, mixed>  $database
     * @return array{database_driver: string, database_path: string, size_bytes: int, manifest_size_bytes: int}
     */
    private function writeSqliteArtifact(string $directory, array $database): array
    {
        $source = (string) ($database['database'] ?? '');

        if ($source === '' || $source === ':memory:' || ! is_file($source)) {
            throw new RuntimeException('No se encontro un archivo SQLite respaldable.');
        }

        $contents = file_get_contents($source);

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer la base SQLite para backup.');
        }

        $path = "{$directory}/database.sqlite";
        Storage::disk('private')->put($path, $contents);

        return [
            'database_driver' => 'sqlite',
            'database_path' => $path,
            'size_bytes' => strlen($contents),
            'manifest_size_bytes' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $database
     * @return array{database_driver: string, database_path: string, size_bytes: int, manifest_size_bytes: int}
     */
    private function writePostgresArtifact(string $directory, array $database): array
    {
        $path = "{$directory}/database.dump";
        $temporaryPath = Storage::disk('private')->path($path);

        if (! is_dir(dirname($temporaryPath))) {
            mkdir(dirname($temporaryPath), 0750, true);
        }

        $process = new Process(array_filter([
            'pg_dump',
            '--format=custom',
            '--no-owner',
            '--no-acl',
            '--file='.$temporaryPath,
            '--host='.($database['host'] ?? '127.0.0.1'),
            '--port='.($database['port'] ?? 5432),
            '--username='.($database['username'] ?? ''),
            (string) ($database['database'] ?? ''),
        ], fn (string $value): bool => $value !== '--username=' && $value !== ''));

        $process->setTimeout(300);
        $process->run(null, array_filter([
            'PGPASSWORD' => (string) ($database['password'] ?? ''),
        ]));

        if (! $process->isSuccessful() || ! is_file($temporaryPath)) {
            @unlink($temporaryPath);

            throw new RuntimeException('No se pudo generar backup PostgreSQL con pg_dump. Verifica que pg_dump este instalado y que la base responda.');
        }

        $size = filesize($temporaryPath);

        return [
            'database_driver' => 'pgsql',
            'database_path' => $path,
            'size_bytes' => $size === false ? 0 : $size,
            'manifest_size_bytes' => 0,
        ];
    }

    /**
     * @param  array{database_driver: string, database_path: string, size_bytes: int, manifest_size_bytes: int}  $artifact
     */
    private function writeManifest(BackupRun $backupRun, array &$artifact, string $trigger): string
    {
        $path = "backups/{$backupRun->uuid}/manifest.json";
        $manifest = json_encode([
            'backup_run_uuid' => $backupRun->uuid,
            'created_at' => now()->toISOString(),
            'trigger' => $trigger,
            'version' => config('doxticket.version', 'dev'),
            'database_driver' => $artifact['database_driver'],
            'database_path' => $artifact['database_path'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($manifest === false) {
            throw new RuntimeException('No se pudo generar el manifiesto de backup.');
        }

        Storage::disk('private')->put($path, $manifest);
        $artifact['manifest_size_bytes'] = strlen($manifest);

        return $path;
    }

    private function sanitizeError(string $message): string
    {
        $message = preg_replace('/password\s*=\s*[^\\s]+/i', 'password=[redacted]', $message) ?? $message;
        $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $message) ?? $message;

        return mb_substr($message, 0, 300);
    }
}
