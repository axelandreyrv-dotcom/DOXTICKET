<?php

namespace Tests\Unit\Admin;

use App\Models\SystemSetting;
use App\Services\Admin\GlobalMailConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GlobalMailConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_global_smtp_settings_from_database(): void
    {
        Config::set('mail.default', 'log');
        Config::set('mail.mailers.smtp.host', '127.0.0.1');
        Config::set('mail.mailers.smtp.port', 2525);
        Config::set('mail.mailers.smtp.username', null);
        Config::set('mail.mailers.smtp.password', null);
        Config::set('mail.mailers.smtp.scheme', null);
        Config::set('mail.from.address', 'hello@example.com');
        Config::set('mail.from.name', 'DoxTicket');

        SystemSetting::put('mail.global.mailer', 'smtp');
        SystemSetting::put('mail.global.host', 'smtp.example.test');
        SystemSetting::put('mail.global.port', 587);
        SystemSetting::put('mail.global.encryption', 'tls');
        SystemSetting::put('mail.global.username', 'notifier@example.test');
        SystemSetting::put('mail.global.password', 'smtp-secret-value', true);
        SystemSetting::put('mail.global.from_address', 'support@example.test');
        SystemSetting::put('mail.global.from_name', 'DoxTicket Support');

        app(GlobalMailConfiguration::class)->apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertNull(config('mail.mailers.smtp.scheme'));
        $this->assertSame('tls', config('mail.mailers.smtp.encryption'));
        $this->assertSame('notifier@example.test', config('mail.mailers.smtp.username'));
        $this->assertSame('smtp-secret-value', config('mail.mailers.smtp.password'));
        $this->assertSame('support@example.test', config('mail.from.address'));
        $this->assertSame('DoxTicket Support', config('mail.from.name'));
    }

    public function test_it_keeps_env_mail_config_when_database_settings_are_absent(): void
    {
        Config::set('mail.default', 'log');
        Config::set('mail.from.address', 'hello@example.com');

        app(GlobalMailConfiguration::class)->apply();

        $this->assertSame('log', config('mail.default'));
        $this->assertSame('hello@example.com', config('mail.from.address'));
    }
}
