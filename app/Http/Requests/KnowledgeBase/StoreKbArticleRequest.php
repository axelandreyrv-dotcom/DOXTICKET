<?php

namespace App\Http\Requests\KnowledgeBase;

use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKbArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = app(TenantContext::class)->membership()?->role;

        return in_array($role, ['admin', 'supervisor'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'body_markdown' => ['required', 'string', 'max:20000'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }
}
