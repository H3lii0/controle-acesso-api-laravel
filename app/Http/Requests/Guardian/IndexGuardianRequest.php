<?php

namespace App\Http\Requests\Guardian;

use Illuminate\Foundation\Http\FormRequest;

class IndexGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['required', 'string', 'min:2', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.required' => 'Informe pelo menos parte do nome ou e-mail do responsável.',
            'search.min' => 'A busca deve ter pelo menos 2 caracteres.',
            'search.max' => 'A busca deve ter no máximo 100 caracteres.',
            'per_page.integer' => 'A quantidade por página deve ser um número inteiro.',
            'per_page.min' => 'A quantidade por página deve ser de pelo menos 1 registro.',
            'per_page.max' => 'A quantidade por página deve ser de no máximo 50 registros.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->input('search')),
        ]);
    }
}
