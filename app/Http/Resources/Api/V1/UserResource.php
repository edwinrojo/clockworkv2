<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'name' => $this->name,
            'email' => $this->email,
            'employee_number' => $this->employee_number,
            'department_id' => $this->department_id,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
        ];
    }
}
