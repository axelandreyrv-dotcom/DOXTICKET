<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Support\Tenant\TenantContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('app.placeholder', [
            'company' => app(TenantContext::class)->company(),
            'membership' => app(TenantContext::class)->membership(),
        ]);
    }
}
