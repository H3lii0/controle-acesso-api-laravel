<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enrollment_number' => $this->enrollment_number,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'school_class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'guardian' => new GuardianResource($this->whenLoaded('guardian')),
            'biometric' => [
                'captured' => $this->relationLoaded('biometricCredential') && $this->biometricCredential !== null,
                'identifier' => $this->when(
                    $this->relationLoaded('biometricCredential') && $this->biometricCredential !== null,
                    $this->biometricCredential?->identifier,
                ),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
