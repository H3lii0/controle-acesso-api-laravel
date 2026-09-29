<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'account_status' => $this->account_status->value,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'students_count' => $this->whenCounted('guardedStudents'),
        ];
    }
}
