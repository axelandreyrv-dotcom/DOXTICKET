<?php

namespace App\Http\Requests\Mail;

use App\Models\MailAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMailAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:imap_smtp'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'from_email' => ['required', 'email:rfc', 'max:180'],
            'host_imap' => ['required', 'string', 'max:255'],
            'port_imap' => ['required', 'integer', 'between:1,65535'],
            'security_imap' => ['required', 'in:none,ssl,tls,starttls'],
            'host_smtp' => ['required', 'string', 'max:255'],
            'port_smtp' => ['required', 'integer', 'between:1,65535'],
            'security_smtp' => ['required', 'in:none,ssl,tls,starttls'],
            'username' => ['nullable', 'string', 'max:180'],
            'password' => ['nullable', 'string', 'max:1000'],
            'folder_in' => ['required', 'string', 'max:120'],
            'auto_reply_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (MailAccount::query()->exists()) {
                    return;
                }

                if (blank($this->input('password'))) {
                    $validator->errors()->add('password', 'La contrasena es obligatoria para la primera configuracion.');
                }
            },
        ];
    }
}
