<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'account_type' => $this->account_type->value,
            'account_status' => $this->account_status->value,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
        ];
    }
}
