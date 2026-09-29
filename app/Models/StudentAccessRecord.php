<?php

namespace App\Models;

use Database\Factories\StudentAccessRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'access_date', 'entered_at', 'exited_at'])]
class StudentAccessRecord extends Model
{
    /** @use HasFactory<StudentAccessRecordFactory> */
    use HasFactory;

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isInsideSchool(): bool
    {
        return $this->entered_at !== null && $this->exited_at === null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_date' => 'date',
            'entered_at' => 'immutable_datetime',
            'exited_at' => 'immutable_datetime',
        ];
    }
}
