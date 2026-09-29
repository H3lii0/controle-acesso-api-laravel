<?php

namespace App\Http\Requests\Access;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAccessRecordRequest extends FormRequest
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
            'date' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
            'school_class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],
            'status' => ['nullable', 'string', Rule::in(['inside', 'completed'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.date_format' => 'Informe a data no formato AAAA-MM-DD.',
            'search.max' => 'A busca deve ter no máximo 100 caracteres.',
            'school_class_id.exists' => 'A turma informada não existe.',
            'status.in' => 'A situação deve ser inside ou completed.',
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
