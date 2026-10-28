<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolSettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'digits_between:10,11'],
            'address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', Rule::in(['America/Recife'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'contact_email' => trim((string) $this->input('contact_email')) ?: null,
            'phone' => preg_replace('/\D+/', '', trim((string) $this->input('phone'))) ?: null,
            'address' => trim((string) $this->input('address')) ?: null,
        ]);
    }
}
