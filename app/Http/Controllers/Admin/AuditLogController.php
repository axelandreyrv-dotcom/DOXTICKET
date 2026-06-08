<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use App\Services\Admin\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.audit.index', [
            'logs' => $this->logs($filters),
            'filters' => $filters,
            'actions' => $this->actions(),
            'companies' => $this->companies(),
            'actors' => $this->actors(),
        ]);
    }

    public function export(Request $request, AuditLogger $auditLogger): StreamedResponse
    {
        $filters = $this->filters($request);
        $filename = 'doxticket-audit-'.now()->format('Ymd-His').'.csv';
        $logs = $this->auditQuery($filters)
            ->oldest('created_at')
            ->limit(5000)
            ->get();

        $auditLogger->record($request, 'admin.audit.exported', null, [
            'filters' => $filters,
            'row_count' => $logs->count(),
            'limit' => 5000,
        ]);

        return response()->streamDownload(function () use ($logs): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['created_at', 'action', 'company', 'actor', 'subject', 'meta']);

            $logs
                ->each(function (AuditLog $log) use ($output): void {
                    fputcsv($output, [
                        $log->created_at?->toIso8601String(),
                        $log->action,
                        $log->company?->name ?? 'Instalación',
                        $this->actorLabel($log),
                        $this->subjectLabel($log),
                        json_encode($this->sanitizeMeta($log->meta ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{q: string|null, action: string|null, company_id: string|null, actor_user_id: string|null, date_from: string|null, date_to: string|null}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:120'],
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'actor_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return [
            'q' => $validated['q'] ?? null,
            'action' => $validated['action'] ?? null,
            'company_id' => isset($validated['company_id']) ? (string) $validated['company_id'] : null,
            'actor_user_id' => isset($validated['actor_user_id']) ? (string) $validated['actor_user_id'] : null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ];
    }

    /**
     * @param  array{q: string|null, action: string|null, company_id: string|null, actor_user_id: string|null, date_from: string|null, date_to: string|null}  $filters
     * @return LengthAwarePaginator<int, array{
     *     created_at: Carbon|null,
     *     action: string,
     *     company: string,
     *     actor: string,
     *     subject: string,
     *     meta: array<mixed>
     * }>
     */
    private function logs(array $filters): LengthAwarePaginator
    {
        return $this->auditQuery($filters)
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => [
                'created_at' => $log->created_at,
                'action' => $log->action,
                'company' => $log->company?->name ?? 'Instalación',
                'actor' => $this->actorLabel($log),
                'subject' => $this->subjectLabel($log),
                'meta' => $this->sanitizeMeta($log->meta ?? []),
            ]);
    }

    /**
     * @param  array{q: string|null, action: string|null, company_id: string|null, actor_user_id: string|null, date_from: string|null, date_to: string|null}  $filters
     * @return Builder<AuditLog>
     */
    private function auditQuery(array $filters): Builder
    {
        return AuditLog::query()
            ->with(['company', 'actorUser'])
            ->when($filters['q'], function ($query, string $term): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

                $query->where(function ($query) use ($like): void {
                    $query->where('action', 'like', $like)
                        ->orWhere('subject_type', 'like', $like)
                        ->orWhereHas('company', fn ($query) => $query->where('name', 'like', $like))
                        ->orWhereHas('actorUser', fn ($query) => $query
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like));
                });
            })
            ->when($filters['action'], fn ($query, string $action) => $query->where('action', $action))
            ->when($filters['company_id'], fn ($query, string $companyId) => $query->where('company_id', $companyId))
            ->when($filters['actor_user_id'], fn ($query, string $actorUserId) => $query->where('actor_user_id', $actorUserId))
            ->when($filters['date_from'], fn ($query, string $date) => $query->where('created_at', '>=', Carbon::createFromFormat('Y-m-d', $date)->startOfDay()))
            ->when($filters['date_to'], fn ($query, string $date) => $query->where('created_at', '<=', Carbon::createFromFormat('Y-m-d', $date)->endOfDay()));
    }

    /**
     * @return Collection<int, string>
     */
    private function actions()
    {
        return AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    /**
     * @return Collection<int, Company>
     */
    private function companies()
    {
        return Company::query()
            ->whereIn('id', AuditLog::query()->whereNotNull('company_id')->select('company_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, User>
     */
    private function actors()
    {
        return User::query()
            ->whereIn('id', AuditLog::query()->whereNotNull('actor_user_id')->select('actor_user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function actorLabel(AuditLog $log): string
    {
        if (! $log->actorUser) {
            return 'Sistema';
        }

        return "{$log->actorUser->name} ({$log->actorUser->email})";
    }

    private function subjectLabel(AuditLog $log): string
    {
        if (! $log->subject_type || ! $log->subject_id) {
            return 'Sin sujeto';
        }

        return class_basename($log->subject_type).' #'.$log->subject_id;
    }

    /**
     * @param  array<mixed>  $meta
     * @return array<mixed>
     */
    private function sanitizeMeta(array $meta): array
    {
        $clean = [];

        foreach ($meta as $key => $value) {
            $clean[$key] = $this->isSensitiveKey((string) $key)
                ? '[redacted]'
                : (is_array($value) ? $this->sanitizeMeta($value) : $value);
        }

        return $clean;
    }

    private function isSensitiveKey(string $key): bool
    {
        return preg_match('/password|contrasena|contraseña|token|secret|authorization|cookie|credential|client_secret|api_key/i', $key) === 1;
    }
}
