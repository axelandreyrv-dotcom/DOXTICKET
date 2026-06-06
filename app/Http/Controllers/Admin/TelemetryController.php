<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\Admin\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelemetryController extends Controller
{
    public function update(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'telemetry_enabled' => ['required', 'boolean'],
        ]);

        $previousValue = SystemSetting::get('telemetry.enabled', false) === true;

        SystemSetting::put('telemetry.enabled', (bool) $data['telemetry_enabled']);

        $auditLogger->record($request, 'admin.telemetry.updated', null, [
            'from' => $previousValue,
            'to' => (bool) $data['telemetry_enabled'],
        ]);

        return redirect('/admin')->with('status', 'Telemetría actualizada.');
    }
}
