<?php

namespace App\Http\Requests\Admin;

use App\Enums\SchoolShift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSchoolClassRequest extends FormRequest
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
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'shift' => ['nullable', 'string', Rule::enum(SchoolShift::class)],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.max' => 'A busca deve ter no máximo 100 caracteres.',
            'shift.enum' => 'O turno informado é inválido.',
            'is_active.boolean' => 'A situação da turma deve ser verdadeira ou falsa.',
            'per_page.integer' => 'A quantidade por página deve ser um número inteiro.',
            'per_page.min' => 'A quantidade por página deve ser de pelo menos 1 registro.',
            'per_page.max' => 'A quantidade por página deve ser de no máximo 100 registros.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $search = trim((string) $this->input('search'));

        $this->merge([
            'search' => $search !== '' ? $search : null,
        ]);
    }
}
