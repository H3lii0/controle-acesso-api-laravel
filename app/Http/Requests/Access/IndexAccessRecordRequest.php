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
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
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
            'date_from.date_format' => 'Informe a data inicial no formato AAAA-MM-DD.',
            'date_to.date_format' => 'Informe a data final no formato AAAA-MM-DD.',
            'date_to.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
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
        $defaultDate = (string) ($this->input('date') ?: now(config('school.timezone'))->toDateString());
        $dateFrom = (string) ($this->input('date_from') ?: $defaultDate);
        $dateTo = (string) ($this->input('date_to') ?: $dateFrom);

        $this->merge([
            'search' => $search !== '' ? $search : null,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);
    }
}
