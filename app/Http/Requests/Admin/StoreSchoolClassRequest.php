<?php

namespace App\Http\Requests\Admin;

use App\Enums\SchoolShift;
use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSchoolClassRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'shift' => ['required', 'string', Rule::enum(SchoolShift::class)],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $alreadyExists = SchoolClass::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($this->string('name')->toString())])
                ->where('shift', $this->string('shift')->toString())
                ->exists();

            if ($alreadyExists) {
                $validator->errors()->add(
                    'name',
                    'Já existe uma turma com este nome no turno informado.',
                );
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da turma.',
            'name.max' => 'O nome da turma deve ter no máximo 100 caracteres.',
            'shift.required' => 'Informe o turno da turma.',
            'shift.enum' => 'O turno informado é inválido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
        ]);
    }
}
