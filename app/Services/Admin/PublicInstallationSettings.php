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
            'github_repository' => $this->stringValue('updates.github_repository', config('doxticket.updates.github_repository', 'doxsuite/doxticket')),
            'donation_paypal_url' => $this->publicStringValue('donations.paypal_url', config('doxticket.donations.paypal_url')),
            'donation_github_sponsors_url' => $this->publicStringValue('donations.github_sponsors_url', config('doxticket.donations.github_sponsors_url')),
            'donation_buy_me_a_coffee_url' => $this->publicStringValue('donations.buy_me_a_coffee_url', config('doxticket.donations.buy_me_a_coffee_url')),
        ];
    }

    public function publicUrl(): string
    {
        return $this->formValues()['public_url'] ?? '';
    }

    public function githubRepository(): string
    {
        $repository = trim((string) ($this->formValues()['github_repository'] ?? ''));

        return $repository !== '' ? $repository : 'doxsuite/doxticket';
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    public function donationLinks(): array
    {
        $links = [
            'PayPal' => $this->formValues()['donation_paypal_url'],
            'GitHub Sponsors' => $this->formValues()['donation_github_sponsors_url'],
            'Buy Me a Coffee' => $this->formValues()['donation_buy_me_a_coffee_url'],
        ];

        return collect($links)
            ->filter(fn ($url): bool => is_string($url) && $this->isPublicUrl($url))
            ->map(fn (string $url, string $label): array => [
                'label' => $label,
                'url' => $url,
            ])
            ->values()
            ->all();
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
            'donation_paypal_url' => 'donations.paypal_url',
            'donation_github_sponsors_url' => 'donations.github_sponsors_url',
            'donation_buy_me_a_coffee_url' => 'donations.buy_me_a_coffee_url',
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
