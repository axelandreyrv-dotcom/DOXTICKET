<?php

namespace App\Http\Requests\Setup;

use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return SystemSetting::get('setup.completed', false) !== true;
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', 'in:es,en'],
            'company_name' => ['required', 'string', 'max:160'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email:rfc', 'max:180', 'unique:users,email'],
            'admin_password' => ['required', 'confirmed', Password::min(10)],
            'telemetry_enabled' => ['nullable', 'boolean'],
        ];
    }
}
