<?php

namespace App\Services\Admin;

use App\Models\MailAccount;
use App\Models\SystemSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemHealthChecker
{
    public function __construct(
        private readonly BackupStatus $backupStatus,
        private readonly BackupPolicySettings $backupPolicy,
    ) {}

    /**
     * @return array<int, HealthCheckResult>
     */
    public function check(): array
    {
        return [
            $this->appKey(),
            $this->appDebug(),
            $this->setupLocked(),
            $this->database(),
            $this->cache(),
            $this->queue(),
            $this->scheduler(),
            $this->workers(),
            $this->storage(),
            $this->smtpGlobal(),
            $this->mailAccounts(),
            $this->backups(),
        ];
    }

    private function appKey(): HealthCheckResult
    {
        $configured = filled((string) config('app.key'));

        return new HealthCheckResult(
            'app_key',
            'APP_KEY',
            $configured ? 'ok' : 'failed',
            $configured ? 'Clave de aplicacion configurada.' : 'Falta APP_KEY en el entorno.',
        );
    }

    private function appDebug(): HealthCheckResult
    {
        $debug = (bool) config('app.debug');

        return new HealthCheckResult(
            'app_debug',
            'APP_DEBUG',
            $debug ? 'warning' : 'ok',
            $debug ? 'APP_DEBUG esta activo; debe apagarse en produccion.' : 'Debug apagado para produccion.',
        );
    }

    private function setupLocked(): HealthCheckResult
    {
        $locked = SystemSetting::get('setup.completed', false) === true;

        return new HealthCheckResult(
            'setup_locked',
            'Setup',
            $locked ? 'ok' : 'failed',
            $locked ? 'Instalador bloqueado despues de finalizar setup.' : 'El instalador sigue disponible.',
        );
    }

    private function database(): HealthCheckResult
    {
        try {
            DB::select('select 1');

            return new HealthCheckResult('database', 'PostgreSQL', 'ok', 'Conexion de base de datos disponible.');
        } catch (Throwable) {
            return new HealthCheckResult('database', 'PostgreSQL', 'failed', 'No se pudo conectar a la base de datos.');
        }
    }

    private function cache(): HealthCheckResult
    {
        try {
            $key = 'doxticket:health:cache';
            Cache::put($key, 'ok', 10);
            $available = Cache::get($key) === 'ok';
            Cache::forget($key);

            return new HealthCheckResult(
                'cache',
                'Redis / cache',
                $available ? 'ok' : 'failed',
                $available ? 'Cache disponible.' : 'La cache no devuelve escrituras recientes.',
            );
        } catch (Throwable) {
            return new HealthCheckResult('cache', 'Redis / cache', 'failed', 'No se pudo validar la cache.');
        }
    }

    private function queue(): HealthCheckResult
    {
        if (! Schema::hasTable('failed_jobs')) {
            return new HealthCheckResult('queue', 'Colas', 'warning', 'Tabla failed_jobs no disponible para revisar errores.');
        }

        $failedJobs = DB::table('failed_jobs')->count();

        return new HealthCheckResult(
            'queue',
            'Colas',
            $failedJobs > 0 ? 'warning' : 'ok',
            $failedJobs > 0
                ? "{$failedJobs} job(s) fallidos requieren revision."
                : 'Sin jobs fallidos registrados.',
        );
    }

    private function scheduler(): HealthCheckResult
    {
        return $this->heartbeatCheck(
            'scheduler',
            'Scheduler',
            'doxticket:health:scheduler:last_run',
            'Scheduler activo recientemente.',
            'No hay heartbeat reciente del scheduler. Revisa cron o schedule:work.',
        );
    }

    private function workers(): HealthCheckResult
    {
        return $this->heartbeatCheck(
            'workers',
            'Workers',
            'doxticket:health:workers:last_run',
            'Worker de colas activo recientemente.',
            'No hay heartbeat reciente de workers. Revisa queue:work o Supervisor.',
        );
    }

    private function storage(): HealthCheckResult
    {
        $writable = is_writable(storage_path()) && is_writable(base_path('bootstrap/cache'));

        return new HealthCheckResult(
            'storage',
            'Storage',
            $writable ? 'ok' : 'failed',
            $writable ? 'Storage y cache de bootstrap escribibles.' : 'Revisar permisos de storage o bootstrap/cache.',
        );
    }

    private function smtpGlobal(): HealthCheckResult
    {
        $defaultMailer = (string) config('mail.default');
        $mailer = config("mail.mailers.{$defaultMailer}", []);
        $transport = (string) ($mailer['transport'] ?? '');
        $host = (string) ($mailer['host'] ?? '');
        $from = (string) config('mail.from.address');
        $configured = $transport === 'smtp'
            && filled($host)
            && ! in_array($host, ['127.0.0.1', 'localhost'], true)
            && filled($from);

        return new HealthCheckResult(
            'smtp_global',
            'SMTP global',
            $configured ? 'ok' : 'warning',
            $configured
                ? 'SMTP global configurado para correos del sistema.'
                : 'SMTP global no configurado para produccion. Revisa MAIL_MAILER, MAIL_HOST y MAIL_FROM_ADDRESS.',
        );
    }

    private function mailAccounts(): HealthCheckResult
    {
        $active = MailAccount::query()->where('is_active', true)->count();
        $withErrors = MailAccount::query()
            ->where('is_active', true)
            ->whereNotNull('last_error')
            ->count();

        if ($active === 0) {
            return new HealthCheckResult('mail_accounts', 'Correo', 'warning', 'No hay cuentas de soporte activas.');
        }

        return new HealthCheckResult(
            'mail_accounts',
            'Correo',
            $withErrors > 0 ? 'warning' : 'ok',
            $withErrors > 0
                ? "{$withErrors} cuenta activa con error reciente. Revisar ajustes de correo."
                : "{$active} cuenta(s) de soporte activas sin errores recientes.",
        );
    }

    private function backups(): HealthCheckResult
    {
        if (! Schema::hasTable('backup_runs')) {
            return new HealthCheckResult('backups', 'Backups', 'warning', 'Tabla backup_runs no disponible.');
        }

        $recentSuccessHours = $this->backupPolicy->recentSuccessHours();

        if (! $this->backupStatus->hasRecentSuccessfulBackup($recentSuccessHours)) {
            return new HealthCheckResult(
                'backups',
                'Backups',
                'warning',
                "No hay backup exitoso reciente en las ultimas {$recentSuccessHours} horas. Revisa la configuracion antes de actualizar.",
            );
        }

        $latest = $this->backupStatus->latestSuccessful();

        return new HealthCheckResult(
            'backups',
            'Backups',
            'ok',
            'Ultimo backup exitoso disponible en '.$latest?->destination.'.',
        );
    }

    private function heartbeatCheck(string $key, string $label, string $cacheKey, string $okMessage, string $warningMessage): HealthCheckResult
    {
        $value = Cache::get($cacheKey);

        if (! is_string($value)) {
            return new HealthCheckResult($key, $label, 'warning', $warningMessage);
        }

        try {
            $heartbeat = Carbon::parse($value);
        } catch (Throwable) {
            return new HealthCheckResult($key, $label, 'warning', $warningMessage);
        }

        $recent = $heartbeat->greaterThanOrEqualTo(now()->subMinutes(10));

        return new HealthCheckResult(
            $key,
            $label,
            $recent ? 'ok' : 'warning',
            $recent ? $okMessage : $warningMessage,
        );
    }
}
