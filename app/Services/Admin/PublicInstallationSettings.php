<?php

namespace App\Services\Admin;

use App\Models\SystemSetting;

class PublicInstallationSettings
{
    /**
     * @return array<string, string|null>
     */
    public function formValues(): array
    {
        return [
            'public_url' => $this->publicStringValue('installation.public_url', config('app.url')),
            'github_repository' => $this->stringValue('updates.github_repository', config('doxticket.updates.github_repository', 'axelandreyrv-dotcom/DOXTICKET')),
        ];
    }

    public function publicUrl(): string
    {
        return $this->formValues()['public_url'] ?? '';
    }

    public function githubRepository(): string
    {
        $repository = trim((string) ($this->formValues()['github_repository'] ?? ''));

        return $repository !== '' ? $repository : 'axelandreyrv-dotcom/DOXTICKET';
    }

    /**
     * @param  array<string, string|null>  $values
     * @return array<int, string>
     */
    public function update(array $values): array
    {
        $mapping = [
            'public_url' => 'installation.public_url',
            'github_repository' => 'updates.github_repository',
        ];
        $changedKeys = [];

        foreach ($mapping as $field => $key) {
            $newValue = $this->normalizeNullableString($values[$field] ?? null);
            $oldValue = SystemSetting::get($key);

            SystemSetting::put($key, $newValue);

            if ($oldValue !== $newValue) {
                $changedKeys[] = $key;
            }
        }

        return $changedKeys;
    }

    public function isPublicUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($url, PHP_URL_SCHEME), ['https', 'http'], true);
    }

    private function stringValue(string $key, mixed $fallback): ?string
    {
        $setting = SystemSetting::query()->where('key', $key)->first();
        $value = $setting === null ? $fallback : SystemSetting::get($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private function publicStringValue(string $key, mixed $fallback): ?string
    {
        $value = $this->stringValue($key, $fallback);

        return is_string($value) && $this->isPublicUrl($value) ? $value : null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
