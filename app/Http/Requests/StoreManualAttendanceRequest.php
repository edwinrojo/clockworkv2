<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Event $event */
        $event = $this->route('event');

        return $this->user()->can('manageAttendances', $event);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
        ];
    }
}
