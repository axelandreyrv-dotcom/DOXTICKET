<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuditLogger;
use App\Services\Admin\BackupStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RollbackController extends Controller
{
    public function store(Request $request, BackupStatus $backupStatus, AuditLogger $auditLogger): RedirectResponse
    {
        if (! $backupStatus->rollbackAvailable()) {
            $auditLogger->record($request, 'admin.rollback.preflight_failed', null, [
                'reason' => 'missing_valid_backup',
            ]);

            return redirect('/admin')->with('status', 'Rollback no disponible: falta backup valido.');
        }

        $auditLogger->record($request, 'admin.rollback.preflight_requested', null, [
            'rollback_available' => true,
        ]);

        return redirect('/admin')->with('status', 'Rollback preparado: revisa la guia manual antes de restaurar.');
    }
}
