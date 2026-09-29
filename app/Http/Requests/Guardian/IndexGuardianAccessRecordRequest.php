<?php

namespace App\Http\Requests\Guardian;

use Illuminate\Foundation\Http\FormRequest;

class IndexGuardianAccessRecordRequest extends FormRequest
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
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_from.date_format' => 'Informe a data inicial no formato AAAA-MM-DD.',
            'date_to.date_format' => 'Informe a data final no formato AAAA-MM-DD.',
            'date_to.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'per_page.integer' => 'A quantidade por página deve ser um número inteiro.',
            'per_page.min' => 'A quantidade por página deve ser de pelo menos 1 registro.',
            'per_page.max' => 'A quantidade por página deve ser de no máximo 100 registros.',
        ];
    }
}
