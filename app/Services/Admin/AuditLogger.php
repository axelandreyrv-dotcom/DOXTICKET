<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * @param  array<mixed>  $meta
     */
    public function record(Request $request, string $action, ?Model $subject = null, array $meta = []): AuditLog
    {
        return AuditLog::query()->create([
            'company_id' => $this->companyId($subject),
            'actor_user_id' => $request->user()?->id,
            'actor_membership_id' => null,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $this->sanitizeMeta($meta),
            'ip' => $request->ip(),
            'user_agent' => str($request->userAgent() ?? '')->limit(255, '')->toString(),
            'created_at' => now(),
        ]);
    }

    private function companyId(?Model $subject): ?int
    {
        if ($subject instanceof Company) {
            return $subject->id;
        }

        $companyId = $subject?->getAttribute('company_id');

        return is_numeric($companyId) ? (int) $companyId : null;
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
