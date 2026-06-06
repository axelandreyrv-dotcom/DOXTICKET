<?php

namespace App\Services\Admin;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GitHubReleaseUpdateChecker
{
    public function __construct(private readonly PublicInstallationSettings $publicSettings) {}

    /**
     * @return array{
     *     checked_at: string,
     *     installed_version: string,
     *     latest_version: string|null,
     *     update_available: bool,
     *     release_url: string|null,
     *     release_name: string|null,
     *     changelog: string|null,
     *     error: string|null
     * }
     */
    public function check(): array
    {
        $installedVersion = (string) config('doxticket.version', 'dev');
        $repository = $this->publicSettings->githubRepository();

        try {
            $response = Http::accept('application/vnd.github+json')
                ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
                ->timeout(10)
                ->get("https://api.github.com/repos/{$repository}/releases/latest");

            if (! $response->successful()) {
                throw new \RuntimeException('GitHub latest release request failed.');
            }

            $release = $response->json();
            $latestVersion = (string) ($release['tag_name'] ?? '');
            $status = [
                'checked_at' => now()->toISOString(),
                'installed_version' => $installedVersion,
                'latest_version' => $latestVersion ?: null,
                'update_available' => $latestVersion !== '' && $this->isNewer($latestVersion, $installedVersion),
                'release_url' => $this->publicGitHubUrl($release['html_url'] ?? null),
                'release_name' => $this->plainText($release['name'] ?? $latestVersion),
                'changelog' => $this->plainText(Str::limit((string) ($release['body'] ?? ''), 500, '...')),
                'error' => null,
            ];
        } catch (Throwable) {
            $status = [
                'checked_at' => now()->toISOString(),
                'installed_version' => $installedVersion,
                'latest_version' => null,
                'update_available' => false,
                'release_url' => null,
                'release_name' => null,
                'changelog' => null,
                'error' => 'No se pudo consultar GitHub Releases.',
            ];
        }

        SystemSetting::put('updates.latest', $status);

        return $status;
    }

    private function isNewer(string $latestVersion, string $installedVersion): bool
    {
        $latest = ltrim(Str::lower($latestVersion), 'v');
        $installed = ltrim(Str::lower($installedVersion), 'v');

        if ($installed === '' || $installed === 'dev') {
            return false;
        }

        return version_compare($latest, $installed, '>');
    }

    private function publicGitHubUrl(mixed $url): ?string
    {
        if (! is_string($url) || ! str_starts_with($url, 'https://github.com/')) {
            return null;
        }

        return $url;
    }

    private function plainText(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim(strip_tags($value));
    }
}
