<?php

namespace Tests\Unit\Admin;

use App\Jobs\Admin\RecordQueueHeartbeatJob;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RecordQueueHeartbeatJobTest extends TestCase
{
    public function test_job_records_recent_worker_heartbeat(): void
    {
        Cache::forget('doxticket:health:workers:last_run');

        app(RecordQueueHeartbeatJob::class)->handle();

        $this->assertNotNull(Cache::get('doxticket:health:workers:last_run'));
    }
}
