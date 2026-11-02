<?php

namespace App\Models;

use App\Enums\AccessDenialReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'reason', 'identifier', 'attempted_at'])]
class AccessDeniedAttempt extends Model
{
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    protected function casts(): array
    {
        return [
            'reason' => AccessDenialReason::class,
            'attempted_at' => 'immutable_datetime',
        ];
    }
}
