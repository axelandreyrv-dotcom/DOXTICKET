<?php

namespace App\Http\Requests\Tickets;

use App\Models\Ticket;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->company()?->id;

        return [
            'subject' => ['nullable', 'string', 'max:255'],
            'body_text' => ['required', 'string', 'max:20000'],
            'requester_email' => ['nullable', 'email:rfc', 'max:180'],
            'requester_name' => ['nullable', 'string', 'max:180'],
            'priority' => ['required', Rule::in(array_keys(Ticket::PRIORITY_LABELS))],
            'ticket_type' => ['required', Rule::in(array_keys(Ticket::TYPE_LABELS))],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('company_id', $companyId)->where('is_active', true),
            ],
            'assigned_to_membership_id' => [
                'nullable',
                'integer',
                Rule::exists('memberships', 'id')->where('company_id', $companyId)->where('status', 'active'),
            ],
        ];
    }
}
