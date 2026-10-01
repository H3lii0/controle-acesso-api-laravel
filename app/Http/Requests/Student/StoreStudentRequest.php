<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
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
            'student' => ['required', 'array'],
            'student.enrollment_number' => ['required', 'string', 'max:30', Rule::unique('students', 'enrollment_number')],
            'student.full_name' => ['required', 'string', 'max:150'],
            'student.date_of_birth' => ['required', 'date', 'before:today'],
            'student.school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
            'student.biometric_captured' => ['sometimes', 'boolean'],
            'guardian' => ['required', 'array'],
            'guardian.mode' => ['required', 'string', Rule::in(['new', 'existing'])],
            'guardian.id' => ['nullable', 'required_if:guardian.mode,existing', 'integer', Rule::exists('users', 'id')],
            'guardian.full_name' => ['nullable', 'required_if:guardian.mode,new', 'string', 'max:150'],
            'guardian.email' => ['nullable', 'required_if:guardian.mode,new', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'guardian.phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->validationMessages();
    }

    protected function prepareForValidation(): void
    {
        $student = (array) $this->input('student', []);
        $guardian = (array) $this->input('guardian', []);

        if (array_key_exists('enrollment_number', $student)) {
            $student['enrollment_number'] = Str::upper(trim((string) $student['enrollment_number']));
        }

        if (array_key_exists('full_name', $student)) {
            $student['full_name'] = trim((string) $student['full_name']);
        }

        $this->normalizeGuardian($guardian);
        $this->merge(compact('student', 'guardian'));
    }

    /**
     * @param  array<string, mixed>  $guardian
     */
    private function normalizeGuardian(array &$guardian): void
    {
        if (array_key_exists('full_name', $guardian)) {
            $guardian['full_name'] = trim((string) $guardian['full_name']);
        }

        if (array_key_exists('email', $guardian)) {
            $guardian['email'] = Str::lower(trim((string) $guardian['email']));
        }

        if (array_key_exists('phone', $guardian)) {
            $phone = trim((string) $guardian['phone']);
            $guardian['phone'] = $phone !== '' ? $phone : null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'student.required' => 'Informe os dados do aluno.',
            'student.enrollment_number.required' => 'Informe a matrícula do aluno.',
            'student.enrollment_number.unique' => 'Já existe um aluno com esta matrícula.',
            'student.enrollment_number.max' => 'A matrícula deve ter no máximo 30 caracteres.',
            'student.full_name.required' => 'Informe o nome completo do aluno.',
            'student.full_name.max' => 'O nome do aluno deve ter no máximo 150 caracteres.',
            'student.date_of_birth.required' => 'Informe a data de nascimento do aluno.',
            'student.date_of_birth.date' => 'Informe uma data de nascimento válida.',
            'student.date_of_birth.before' => 'A data de nascimento deve ser anterior à data atual.',
            'student.school_class_id.required' => 'Selecione a turma do aluno.',
            'student.school_class_id.exists' => 'A turma informada não existe.',
            'student.biometric_captured.boolean' => 'O estado da captura biométrica é inválido.',
            'guardian.required' => 'Informe o responsável pelo aluno.',
            'guardian.mode.required' => 'Informe se o responsável é novo ou existente.',
            'guardian.mode.in' => 'O tipo de vínculo do responsável é inválido.',
            'guardian.id.required_if' => 'Selecione o responsável existente.',
            'guardian.id.exists' => 'O responsável selecionado não existe.',
            'guardian.full_name.required_if' => 'Informe o nome completo do responsável.',
            'guardian.full_name.max' => 'O nome do responsável deve ter no máximo 150 caracteres.',
            'guardian.email.required_if' => 'Informe o e-mail do responsável.',
            'guardian.email.email' => 'Informe um e-mail válido para o responsável.',
            'guardian.email.unique' => 'Já existe uma conta com este e-mail. Selecione o responsável existente.',
            'guardian.phone.max' => 'O telefone deve ter no máximo 20 caracteres.',
        ];
    }
}
