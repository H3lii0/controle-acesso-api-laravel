<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
        $employee = $this->route('employee');

        return [
            'full_name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee instanceof User ? $employee->id : $employee),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'distinct', Rule::exists('permissions', 'key')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Informe o nome completo do funcionário.',
            'full_name.max' => 'O nome completo deve ter no máximo 150 caracteres.',
            'email.required' => 'Informe o e-mail do funcionário.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.unique' => 'Já existe uma conta cadastrada com este e-mail.',
            'phone.max' => 'O telefone deve ter no máximo 20 caracteres.',
            'permissions.required' => 'Selecione pelo menos uma permissão.',
            'permissions.array' => 'As permissões informadas são inválidas.',
            'permissions.min' => 'Selecione pelo menos uma permissão.',
            'permissions.*.distinct' => 'Uma mesma permissão não pode ser selecionada mais de uma vez.',
            'permissions.*.exists' => 'Uma das permissões informadas não existe.',
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
