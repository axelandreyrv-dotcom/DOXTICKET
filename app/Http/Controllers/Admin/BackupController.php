<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuditLogger;
use App\Services\Admin\LocalBackupRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function store(Request $request, LocalBackupRunner $backupRunner, AuditLogger $auditLogger): RedirectResponse
    {
        $backupRun = $backupRunner->run('manual');

        $auditLogger->record($request, 'admin.backup.manual_run', $backupRun, [
            'status' => $backupRun->status,
            'destination' => $backupRun->destination,
            'size_bytes' => $backupRun->size_bytes,
        ]);

        if ($backupRun->status === 'succeeded') {
            return redirect('/admin')->with('status', 'Backup local generado.');
        }

        return redirect('/admin')->with('status', 'No se pudo generar el backup local. Revisa health.');
    }
}
