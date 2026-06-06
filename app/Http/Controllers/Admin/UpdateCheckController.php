<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuditLogger;
use App\Services\Admin\GitHubReleaseUpdateChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateCheckController extends Controller
{
    public function store(Request $request, GitHubReleaseUpdateChecker $updateChecker, AuditLogger $auditLogger): RedirectResponse
    {
        $status = $updateChecker->check();

        $auditLogger->record($request, 'admin.updates.checked', null, [
            'update_available' => $status['update_available'] ?? false,
            'latest_version' => $status['latest_version'] ?? null,
            'has_error' => ($status['error'] ?? null) !== null,
        ]);

        return redirect('/admin')->with(
            'status',
            $status['error'] === null
                ? 'Chequeo de actualizaciones completado.'
                : 'No se pudo revisar actualizaciones. Revisa el panel de updates.'
        );
    }
}
