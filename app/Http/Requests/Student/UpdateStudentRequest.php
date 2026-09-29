<?php

namespace App\Http\Requests\Student;

use App\Models\Student;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends StoreStudentRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $student = $this->route('student');

        $rules['student.enrollment_number'] = [
            'required',
            'string',
            'max:30',
            Rule::unique('students', 'enrollment_number')
                ->ignore($student instanceof Student ? $student->id : $student),
        ];

        return $rules;
    }
}
