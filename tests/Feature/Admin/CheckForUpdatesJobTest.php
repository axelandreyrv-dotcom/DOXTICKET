<?php

namespace Tests\Feature\Admin;

use App\Jobs\Admin\CheckForUpdatesJob;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckForUpdatesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_checks_github_releases_and_persists_update_status(): void
    {
        Config::set('doxticket.version', 'v1.0.0');
        Config::set('doxticket.updates.github_repository', 'doxsuite/doxticket');

        Http::fake([
            'https://api.github.com/repos/doxsuite/doxticket/releases/latest' => Http::response([
                'tag_name' => 'v1.2.0',
                'name' => 'DoxTicket v1.2.0',
                'html_url' => 'https://github.com/doxsuite/doxticket/releases/tag/v1.2.0',
                'body' => 'Release estable',
                'published_at' => '2026-05-31T10:00:00Z',
            ], 200),
        ]);

        app()->call([app(CheckForUpdatesJob::class), 'handle']);

        $this->assertSame('v1.2.0', SystemSetting::get('updates.latest')['latest_version']);
        $this->assertTrue(SystemSetting::get('updates.latest')['update_available']);
    }
}
