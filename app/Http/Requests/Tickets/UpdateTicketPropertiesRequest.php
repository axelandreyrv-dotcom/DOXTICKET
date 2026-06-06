<?php

namespace App\Http\Requests\Tickets;

use App\Models\Ticket;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketPropertiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = app(TenantContext::class)->company()?->id;

        return [
            'status' => ['required', Rule::in(array_keys(Ticket::EDITABLE_STATUS_LABELS))],
            'priority' => ['required', Rule::in(array_keys(Ticket::PRIORITY_LABELS))],
            'ticket_type' => ['required', Rule::in(array_keys(Ticket::TYPE_LABELS))],
            'assigned_to_membership_id' => [
                'nullable',
                'integer',
                Rule::exists('memberships', 'id')->where('company_id', $companyId)->where('status', 'active'),
            ],
        ];
    }
}
