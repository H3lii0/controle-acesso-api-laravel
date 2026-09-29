<?php

namespace App\Http\Requests\Guardian;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $guardian = $this->route('guardian');

        return [
            'full_name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($guardian instanceof User ? $guardian->id : $guardian),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Informe o nome completo do responsável.',
            'full_name.max' => 'O nome deve ter no máximo 150 caracteres.',
            'email.required' => 'Informe o e-mail do responsável.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.unique' => 'Já existe uma conta cadastrada com este e-mail.',
            'phone.max' => 'O telefone deve ter no máximo 20 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = trim((string) $this->input('phone'));

        $this->merge([
            'full_name' => trim((string) $this->input('full_name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone' => $phone !== '' ? $phone : null,
        ]);
    }
}
