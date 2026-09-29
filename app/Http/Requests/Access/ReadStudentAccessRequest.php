<?php

namespace App\Http\Requests\Access;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ReadStudentAccessRequest extends FormRequest
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
            'enrollment_number' => ['required', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'enrollment_number.required' => 'Informe a matrícula do aluno.',
            'enrollment_number.max' => 'A matrícula deve ter no máximo 30 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('enrollment_number')) {
            $this->merge([
                'enrollment_number' => Str::upper(trim((string) $this->input('enrollment_number'))),
            ]);
        }
    }
}
