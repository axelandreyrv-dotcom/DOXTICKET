<?php

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attachment' => ['required', 'file', 'max:'.$this->maxKilobytes()],
        ];
    }

    private function maxKilobytes(): int
    {
        return max(1, (int) ceil(((int) config('doxticket.attachments.max_bytes', 10 * 1024 * 1024)) / 1024));
    }
}
