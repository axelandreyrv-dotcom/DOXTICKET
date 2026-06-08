<?php

namespace App\Services\Admin;

use App\Models\SystemSetting;

class GlobalSmtpSettings
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $settings = null;

    /**
     * @return array{
     *     mail_mailer: string,
     *     mail_host: ?string,
     *     mail_port: int,
     *     mail_encryption: string,
     *     mail_username: ?string,
     *     mail_from_address: ?string,
     *     mail_from_name: ?string,
     *     mail_password_configured: bool
     * }
     */
    public function formValues(): array
    {
        return [
            'mail_mailer' => $this->mailer(),
            'mail_host' => $this->stringSetting('mail.global.host', config('mail.mailers.smtp.host')),
            'mail_port' => $this->intSetting('mail.global.port', (int) config('mail.mailers.smtp.port', 587), 1, 65535),
            'mail_encryption' => $this->encryption(),
            'mail_username' => $this->stringSetting('mail.global.username', config('mail.mailers.smtp.username')),
            'mail_from_address' => $this->stringSetting('mail.global.from_address', config('mail.from.address')),
            'mail_from_name' => $this->stringSetting('mail.global.from_name', config('mail.from.name')),
            'mail_password_configured' => $this->passwordConfigured(),
        ];
    }

    public function mailer(): string
    {
        $mailer = $this->stringSetting('mail.global.mailer', config('mail.default', 'log'));

        return in_array($mailer, ['log', 'smtp'], true) ? $mailer : 'log';
    }

    public function password(): ?string
    {
        return $this->stringSetting('mail.global.password');
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<int, string>
     */
    public function update(array $values): array
    {
        $mapping = [
            'mail_mailer' => ['mail.global.mailer', 'string', false],
            'mail_host' => ['mail.global.host', 'string', false],
            'mail_port' => ['mail.global.port', 'int', false],
            'mail_encryption' => ['mail.global.encryption', 'string', false],
            'mail_username' => ['mail.global.username', 'string', false],
            'mail_from_address' => ['mail.global.from_address', 'string', false],
            'mail_from_name' => ['mail.global.from_name', 'string', false],
        ];
        $changedKeys = [];

        foreach ($mapping as $field => [$key, $type, $secret]) {
            $newValue = $type === 'int'
                ? (int) $values[$field]
                : $this->normalizeNullableString($values[$field] ?? null);
            $oldValue = SystemSetting::get($key);

            SystemSetting::put($key, $newValue, $secret);

            if ($oldValue !== $newValue) {
                $changedKeys[] = $key;
            }
        }

        $password = $this->normalizeNullableString($values['mail_password'] ?? null);

        if ($password !== null) {
            $oldPassword = $this->password();
            SystemSetting::put('mail.global.password', $password, true);

            if ($oldPassword !== $password) {
                $changedKeys[] = 'mail.global.password';
            }
        }

        $this->settings = null;

        return $changedKeys;
    }

    private function encryption(): string
    {
        $value = $this->stringSetting('mail.global.encryption');

        if (in_array($value, ['none', 'tls', 'ssl'], true)) {
            return $value;
        }

        $scheme = config('mail.mailers.smtp.scheme');

        return $scheme === 'smtps' ? 'ssl' : 'tls';
    }

    private function passwordConfigured(): bool
    {
        return $this->password() !== null;
    }

    private function stringSetting(string $key, mixed $fallback = null): ?string
    {
        $settings = $this->settings();
        $value = array_key_exists($key, $settings) ? $settings[$key] : $fallback;

        return $this->normalizeNullableString($value);
    }

    private function intSetting(string $key, int $default, int $min, int $max): int
    {
        $settings = $this->settings();
        $value = array_key_exists($key, $settings) ? $settings[$key] : $default;

        if (! is_int($value)) {
            return $default;
        }

        return max($min, min($max, $value));
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $this->settings = [];

        SystemSetting::query()
            ->whereIn('key', [
                'mail.global.mailer',
                'mail.global.host',
                'mail.global.port',
                'mail.global.encryption',
                'mail.global.username',
                'mail.global.password',
                'mail.global.from_address',
                'mail.global.from_name',
            ])
            ->get()
            ->each(function (SystemSetting $setting): void {
                $this->settings[$setting->key] = $setting->decodedValue();
            });

        return $this->settings;
    }
}
