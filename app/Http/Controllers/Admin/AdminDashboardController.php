<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\MailAccount;
use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Admin\BackupStatus;
use App\Services\Admin\SystemHealthChecker;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(
        SystemHealthChecker $healthChecker,
        BackupStatus $backupStatus,
    ): View {
        $latestBackup = $backupStatus->latestSuccessful();

        return view('admin.dashboard', [
            'checks' => $healthChecker->check(),
            'summary' => [
                'companies' => Company::query()->count(),
                'users' => User::query()->count(),
                'tickets' => Ticket::query()->count(),
                'mail_accounts' => MailAccount::query()->where('is_active', true)->count(),
            ],
            'updateStatus' => SystemSetting::get('updates.latest'),
            'telemetry' => [
                'enabled' => SystemSetting::get('telemetry.enabled', false) === true,
            ],
            'backup' => [
                'latest' => $latestBackup,
                'latest_size' => $backupStatus->humanSize($latestBackup?->size_bytes),
                'recent' => $backupStatus->recentRuns(),
                'rollback_available' => $backupStatus->rollbackAvailable(),
            ],
            'backupStatus' => $backupStatus,
            'version' => config('doxticket.version', 'dev'),
        ]);
    }
}
