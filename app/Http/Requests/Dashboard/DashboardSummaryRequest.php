<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\SchoolShift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardSummaryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'period' => ['nullable', Rule::in(['today', 'last_7_days'])],
            'school_class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],
            'shift' => ['nullable', Rule::enum(SchoolShift::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'date.date_format' => 'Informe a data no formato AAAA-MM-DD.',
            'period.in' => 'O período deve ser today ou last_7_days.',
            'school_class_id.exists' => 'A turma informada não existe.',
            'shift.enum' => 'O turno informado é inválido.',
        ];
    }
}
