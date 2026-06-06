<?php

namespace App\Services\Admin;

use App\Models\SystemSetting;

class BackupPolicySettings
{
    public function recentSuccessHours(): int
    {
        return $this->intSetting('backups.recent_success_hours', 24, 1, 168);
    }

    public function retentionDays(): int
    {
        return $this->intSetting('backups.retention_days', 30, 1, 365);
    }

    public function scheduleEnabled(): bool
    {
        return SystemSetting::get('backups.schedule_enabled', false) === true;
    }

    public function scheduleHour(): int
    {
        return $this->intSetting('backups.schedule_hour', 2, 0, 23);
    }

    public function lastScheduledRunDate(): ?string
    {
        $date = SystemSetting::get('backups.last_scheduled_run_date');

        return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            ? $date
            : null;
    }

    public function markScheduledRunDate(string $date): void
    {
        SystemSetting::put('backups.last_scheduled_run_date', $date);
    }

    /**
     * @return array{backup_recent_success_hours: int, backup_retention_days: int, backup_schedule_enabled: bool, backup_schedule_hour: int}
     */
    public function formValues(): array
    {
        return [
            'backup_recent_success_hours' => $this->recentSuccessHours(),
            'backup_retention_days' => $this->retentionDays(),
            'backup_schedule_enabled' => $this->scheduleEnabled(),
            'backup_schedule_hour' => $this->scheduleHour(),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<int, string>
     */
    public function update(array $values): array
    {
        $mapping = [
            'backup_recent_success_hours' => 'backups.recent_success_hours',
            'backup_retention_days' => 'backups.retention_days',
            'backup_schedule_enabled' => 'backups.schedule_enabled',
            'backup_schedule_hour' => 'backups.schedule_hour',
        ];
        $changedKeys = [];

        foreach ($mapping as $field => $key) {
            $newValue = $field === 'backup_schedule_enabled'
                ? (bool) ($values[$field] ?? false)
                : (int) $values[$field];
            $oldValue = SystemSetting::get($key);

            SystemSetting::put($key, $newValue);

            if ($oldValue !== $newValue) {
                $changedKeys[] = $key;
            }
        }

        return $changedKeys;
    }

    private function intSetting(string $key, int $default, int $min, int $max): int
    {
        $value = SystemSetting::get($key, $default);

        if (! is_int($value)) {
            return $default;
        }

        return max($min, min($max, $value));
    }
}
