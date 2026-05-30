<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Support\Tenant\TenantContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('app.placeholder', [
            'company' => app(TenantContext::class)->company(),
            'membership' => app(TenantContext::class)->membership(),
            'metrics' => [
                'new' => Ticket::query()->where('status', 'new')->count(),
                'active' => Ticket::query()
                    ->whereIn('status', ['open', 'in_progress', 'waiting_customer', 'waiting_internal', 'reopened'])
                    ->count(),
                'assigned' => Ticket::query()
                    ->where('assigned_to_membership_id', app(TenantContext::class)->membership()?->id)
                    ->whereIn('status', Ticket::ACTIVE_STATUSES)
                    ->count(),
                'resolved' => Ticket::query()->where('status', 'resolved')->count(),
            ],
            'recentTickets' => Ticket::query()
                ->with(['assignedToMembership.user', 'category'])
                ->whereIn('status', Ticket::ACTIVE_STATUSES)
                ->orderByDesc('last_activity_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
