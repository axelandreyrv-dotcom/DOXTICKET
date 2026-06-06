<?php

namespace Tests\Unit\Admin;

use App\Models\SystemSetting;
use App\Services\Admin\GitHubReleaseUpdateChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GitHubReleaseUpdateCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_latest_stable_release_and_stores_update_status_without_sensitive_query_data(): void
    {
        Config::set('doxticket.version', 'v1.0.0');
        Config::set('doxticket.updates.github_repository', 'doxsuite/doxticket');

        Http::fake([
            'https://api.github.com/repos/doxsuite/doxticket/releases/latest' => Http::response([
                'tag_name' => 'v1.1.0',
                'name' => 'DoxTicket v1.1.0',
                'html_url' => 'https://github.com/doxsuite/doxticket/releases/tag/v1.1.0',
                'body' => "Cambios importantes\n\n- Mejoras de correo",
                'published_at' => '2026-05-31T10:00:00Z',
            ], 200),
        ]);

        $status = app(GitHubReleaseUpdateChecker::class)->check();

        $this->assertTrue($status['update_available']);
        $this->assertSame('v1.0.0', $status['installed_version']);
        $this->assertSame('v1.1.0', $status['latest_version']);
        $this->assertSame($status, SystemSetting::get('updates.latest'));

        Http::assertSentCount(1);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.github.com/repos/doxsuite/doxticket/releases/latest'
                && $request->method() === 'GET'
                && $request->hasHeader('Accept', 'application/vnd.github+json')
                && $request->hasHeader('X-GitHub-Api-Version', '2022-11-28');
        });
    }

    public function test_it_reports_no_update_when_installed_version_matches_latest_release(): void
    {
        Config::set('doxticket.version', 'v1.1.0');
        Config::set('doxticket.updates.github_repository', 'doxsuite/doxticket');

        Http::fake([
            'https://api.github.com/repos/doxsuite/doxticket/releases/latest' => Http::response([
                'tag_name' => 'v1.1.0',
                'name' => 'DoxTicket v1.1.0',
                'html_url' => 'https://github.com/doxsuite/doxticket/releases/tag/v1.1.0',
                'body' => '',
                'published_at' => '2026-05-31T10:00:00Z',
            ], 200),
        ]);

        $status = app(GitHubReleaseUpdateChecker::class)->check();

        $this->assertFalse($status['update_available']);
        $this->assertSame('v1.1.0', $status['latest_version']);
        $this->assertNull($status['error']);
    }

    public function test_it_stores_sanitized_error_when_github_check_fails(): void
    {
        Config::set('doxticket.version', 'v1.0.0');
        Config::set('doxticket.updates.github_repository', 'doxsuite/doxticket');

        Http::fake([
            'https://api.github.com/repos/doxsuite/doxticket/releases/latest' => Http::response([
                'message' => 'Bad credentials token=secret-value',
            ], 500),
        ]);

        $status = app(GitHubReleaseUpdateChecker::class)->check();

        $this->assertFalse($status['update_available']);
        $this->assertSame('No se pudo consultar GitHub Releases.', $status['error']);
        $this->assertSame($status, SystemSetting::get('updates.latest'));
    }

    public function test_it_prefers_repository_saved_in_system_settings(): void
    {
        Config::set('doxticket.version', 'v1.0.0');
        Config::set('doxticket.updates.github_repository', 'doxsuite/doxticket');
        SystemSetting::put('updates.github_repository', 'acme/doxticket');

        Http::fake([
            'https://api.github.com/repos/acme/doxticket/releases/latest' => Http::response([
                'tag_name' => 'v1.2.0',
                'name' => 'DoxTicket v1.2.0',
                'html_url' => 'https://github.com/acme/doxticket/releases/tag/v1.2.0',
                'body' => 'Release estable',
            ], 200),
        ]);

        $status = app(GitHubReleaseUpdateChecker::class)->check();

        $this->assertSame('v1.2.0', $status['latest_version']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.github.com/repos/acme/doxticket/releases/latest';
        });
    }
}
