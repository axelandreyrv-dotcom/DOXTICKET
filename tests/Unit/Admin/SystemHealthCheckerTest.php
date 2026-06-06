<?php

namespace Tests\Unit\Admin;

use App\Models\BackupRun;
use App\Models\MailAccount;
use App\Models\SystemSetting;
use App\Services\Admin\SystemHealthChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SystemHealthCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_checker_reports_required_installation_checks(): void
    {
        SystemSetting::put('setup.completed', true);

        $checks = collect(app(SystemHealthChecker::class)->check())->keyBy('key');

        $this->assertSame('ok', $checks->get('app_key')->status);
        $this->assertSame('warning', $checks->get('app_debug')->status);
        $this->assertSame('ok', $checks->get('setup_locked')->status);
        $this->assertSame('ok', $checks->get('database')->status);
        $this->assertTrue($checks->has('cache'));
        $this->assertTrue($checks->has('queue'));
        $this->assertTrue($checks->has('scheduler'));
        $this->assertTrue($checks->has('workers'));
        $this->assertTrue($checks->has('storage'));
        $this->assertTrue($checks->has('smtp_global'));
        $this->assertTrue($checks->has('backups'));
    }

    public function test_scheduler_and_worker_health_are_ok_with_recent_heartbeats(): void
    {
        Cache::put('doxticket:health:scheduler:last_run', now()->toISOString(), now()->addHour());
        Cache::put('doxticket:health:workers:last_run', now()->toISOString(), now()->addHour());

        $checks = collect(app(SystemHealthChecker::class)->check())->keyBy('key');

        $this->assertSame('ok', $checks->get('scheduler')->status);
        $this->assertSame('ok', $checks->get('workers')->status);
    }

    public function test_scheduler_and_worker_health_warn_when_heartbeats_are_missing(): void
    {
        Cache::forget('doxticket:health:scheduler:last_run');
        Cache::forget('doxticket:health:workers:last_run');

        $checks = collect(app(SystemHealthChecker::class)->check())->keyBy('key');

        $this->assertSame('warning', $checks->get('scheduler')->status);
        $this->assertSame('warning', $checks->get('workers')->status);
    }

    public function test_smtp_global_health_is_ok_when_smtp_mailer_is_configured(): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.example.test');
        Config::set('mail.mailers.smtp.port', 587);
        Config::set('mail.from.address', 'noreply@example.test');

        $checks = collect(app(SystemHealthChecker::class)->check())->keyBy('key');

        $smtp = $checks->get('smtp_global');

        $this->assertSame('ok', $smtp->status);
        $this->assertStringContainsString('SMTP global configurado', $smtp->message);
        $this->assertStringNotContainsString('noreply@example.test', $smtp->message);
    }

    public function test_mail_account_errors_are_counted_without_exposing_secret_details(): void
    {
        MailAccount::factory()->create([
            'last_error' => 'SMTP password hunter2 failed for user soporte@example.test',
            'is_active' => true,
        ]);

        $checks = collect(app(SystemHealthChecker::class)->check())->keyBy('key');

        $mail = $checks->get('mail_accounts');

        $this->assertSame('warning', $mail->status);
        $this->assertStringContainsString('1 cuenta activa', $mail->message);
        $this->assertStringNotContainsString('hunter2', $mail->message);
        $this->assertStringNotContainsString('soporte@example.test', $mail->message);
    }

    public function test_backups_health_warns_when_there_is_no_recent_successful_backup(): void
    {
        BackupRun::query()->create([
            'status' => 'failed',
            'destination' => 'local',
            'started_at' => now()->subHours(2),
            'finished_at' => now()->subHours(2)->addMinutes(1),
            'error' => 'pg_dump password=secret failed',
        ]);

        $checks = collect(app(SystemHealthChecker::class)->check())->keyBy('key');

        $backups = $checks->get('backups');

        $this->assertSame('warning', $backups->status);
        $this->assertStringContainsString('No hay backup exitoso reciente', $backups->message);
        $this->assertStringNotContainsString('secret', $backups->message);
    }

    public function test_backups_health_is_ok_when_recent_successful_backup_exists(): void
    {
        BackupRun::query()->create([
            'status' => 'succeeded',
            'destination' => 'local',
            'started_at' => now()->subHours(3),
            'finished_at' => now()->subHours(3)->addMinutes(2),
            'size_bytes' => 1024,
        ]);

        $checks = collect(app(SystemHealthChecker::class)->check())->keyBy('key');

        $this->assertSame('ok', $checks->get('backups')->status);
    }

    public function test_backups_health_uses_configured_recent_success_window(): void
    {
        SystemSetting::put('backups.recent_success_hours', 48);

        BackupRun::query()->create([
            'status' => 'succeeded',
            'destination' => 'local',
            'started_at' => now()->subHours(36),
            'finished_at' => now()->subHours(36)->addMinutes(2),
            'size_bytes' => 1024,
        ]);

        $checks = collect(app(SystemHealthChecker::class)->check())->keyBy('key');

        $this->assertSame('ok', $checks->get('backups')->status);
    }
}
