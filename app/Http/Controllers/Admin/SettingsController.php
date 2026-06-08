<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\Admin\AuditLogger;
use App\Services\Admin\BackupPolicySettings;
use App\Services\Admin\PublicInstallationSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(PublicInstallationSettings $publicSettings, BackupPolicySettings $backupPolicy): View
    {
        $form = $publicSettings->formValues();
        $backupForm = $backupPolicy->formValues();

        return view('admin.settings.index', [
            'settings' => [
                'app_url' => $form['public_url'] ?: 'Sin configurar',
                'version' => config('doxticket.version', 'dev'),
                'github_repository' => $form['github_repository'] ?: 'Sin configurar',
                'telemetry_enabled' => SystemSetting::get('telemetry.enabled', false) === true,
                'mail_from' => config('mail.from.address') ?: 'Sin configurar',
                'mail_mailer' => config('mail.default') ?: 'Sin configurar',
                'backup_recent_success_hours' => $backupForm['backup_recent_success_hours'],
                'backup_retention_days' => $backupForm['backup_retention_days'],
            ],
            'form' => array_merge($form, $backupForm),
        ]);
    }

    public function update(
        Request $request,
        PublicInstallationSettings $publicSettings,
        BackupPolicySettings $backupPolicy,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $validated = $request->validate([
            'public_url' => ['nullable', 'string', 'max:255', $this->publicUrlRule($publicSettings)],
            'github_repository' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/'],
            'backup_recent_success_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'backup_retention_days' => ['required', 'integer', 'min:1', 'max:365'],
            'backup_schedule_enabled' => ['nullable', 'boolean'],
            'backup_schedule_hour' => ['required', 'integer', 'min:0', 'max:23'],
        ], [
            'github_repository.regex' => 'Use el formato propietario/repositorio.',
        ], [
            'public_url' => 'URL pública',
            'github_repository' => 'repositorio de releases',
            'backup_recent_success_hours' => 'horas para backup reciente',
            'backup_retention_days' => 'dias de retencion de backups',
            'backup_schedule_enabled' => 'backup automático',
            'backup_schedule_hour' => 'hora del backup automático',
        ]);

        $changedKeys = array_merge(
            $publicSettings->update($validated),
            $backupPolicy->update($validated),
        );

        $auditLogger->record($request, 'admin.settings.updated', null, [
            'changed_keys' => $changedKeys,
        ]);

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Configuración actualizada.');
    }

    /**
     * @return \Closure(string, mixed, \Closure(string): void): void
     */
    private function publicUrlRule(PublicInstallationSettings $publicSettings): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($publicSettings): void {
            if ($value === null || $value === '') {
                return;
            }

            if (! is_string($value) || ! $publicSettings->isPublicUrl($value)) {
                $fail('Use una URL pública http o https válida.');
            }
        };
    }
}
