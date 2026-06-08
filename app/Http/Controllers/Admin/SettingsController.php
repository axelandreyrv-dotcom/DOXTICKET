<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\Admin\AuditLogger;
use App\Services\Admin\BackupPolicySettings;
use App\Services\Admin\GlobalMailConfiguration;
use App\Services\Admin\GlobalSmtpSettings;
use App\Services\Admin\PublicInstallationSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(
        PublicInstallationSettings $publicSettings,
        BackupPolicySettings $backupPolicy,
        GlobalSmtpSettings $globalSmtp,
    ): View {
        $form = $publicSettings->formValues();
        $backupForm = $backupPolicy->formValues();
        $mailForm = $globalSmtp->formValues();

        return view('admin.settings.index', [
            'settings' => [
                'app_url' => $form['public_url'] ?: 'Sin configurar',
                'version' => config('doxticket.version', 'dev'),
                'github_repository' => $form['github_repository'] ?: 'Sin configurar',
                'telemetry_enabled' => SystemSetting::get('telemetry.enabled', false) === true,
                'mail_from' => $mailForm['mail_from_address'] ?: 'Sin configurar',
                'mail_mailer' => $mailForm['mail_mailer'] ?: 'Sin configurar',
                'mail_host' => $mailForm['mail_host'] ?: 'Sin configurar',
                'mail_username' => $mailForm['mail_username'] ?: 'Sin configurar',
                'mail_password_configured' => $mailForm['mail_password_configured'],
                'backup_recent_success_hours' => $backupForm['backup_recent_success_hours'],
                'backup_retention_days' => $backupForm['backup_retention_days'],
            ],
            'form' => array_merge($form, $backupForm, $mailForm),
        ]);
    }

    public function update(
        Request $request,
        PublicInstallationSettings $publicSettings,
        BackupPolicySettings $backupPolicy,
        GlobalSmtpSettings $globalSmtp,
        GlobalMailConfiguration $globalMailConfiguration,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $hasMailInput = $request->hasAny([
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_encryption',
            'mail_username',
            'mail_password',
            'mail_from_address',
            'mail_from_name',
        ]);
        $mailDefaults = $globalSmtp->formValues();
        unset($mailDefaults['mail_password_configured']);
        $request->merge(array_replace($mailDefaults, $request->all()));

        $validated = $request->validate([
            'public_url' => ['nullable', 'string', 'max:255', $this->publicUrlRule($publicSettings)],
            'github_repository' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/'],
            'backup_recent_success_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'backup_retention_days' => ['required', 'integer', 'min:1', 'max:365'],
            'backup_schedule_enabled' => ['nullable', 'boolean'],
            'backup_schedule_hour' => ['required', 'integer', 'min:0', 'max:23'],
            'mail_mailer' => ['required', 'string', 'in:log,smtp'],
            'mail_host' => ['required_if:mail_mailer,smtp', 'nullable', 'string', 'max:255'],
            'mail_port' => ['required_if:mail_mailer,smtp', 'nullable', 'integer', 'min:1', 'max:65535'],
            'mail_encryption' => ['required_if:mail_mailer,smtp', 'nullable', 'string', 'in:none,tls,ssl'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:1000'],
            'mail_from_address' => ['required_if:mail_mailer,smtp', 'nullable', 'email:rfc', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
        ], [
            'github_repository.regex' => 'Use el formato propietario/repositorio.',
            'mail_host.required_if' => 'El servidor SMTP es obligatorio cuando el mailer es SMTP.',
            'mail_port.required_if' => 'El puerto SMTP es obligatorio cuando el mailer es SMTP.',
            'mail_encryption.required_if' => 'La seguridad SMTP es obligatoria cuando el mailer es SMTP.',
            'mail_from_address.required_if' => 'El remitente del sistema es obligatorio cuando el mailer es SMTP.',
        ], [
            'public_url' => 'URL pública',
            'github_repository' => 'repositorio de releases',
            'backup_recent_success_hours' => 'horas para backup reciente',
            'backup_retention_days' => 'dias de retencion de backups',
            'backup_schedule_enabled' => 'backup automático',
            'backup_schedule_hour' => 'hora del backup automático',
            'mail_mailer' => 'mailer del sistema',
            'mail_host' => 'servidor SMTP',
            'mail_port' => 'puerto SMTP',
            'mail_encryption' => 'seguridad SMTP',
            'mail_username' => 'usuario SMTP',
            'mail_password' => 'contraseña SMTP',
            'mail_from_address' => 'remitente del sistema',
            'mail_from_name' => 'nombre del remitente',
        ]);

        $changedKeys = array_merge(
            $publicSettings->update($validated),
            $backupPolicy->update($validated),
            $hasMailInput ? $globalSmtp->update($validated) : [],
        );

        if ($hasMailInput) {
            $globalMailConfiguration->apply();
        }

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
