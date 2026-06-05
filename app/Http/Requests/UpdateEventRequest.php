<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesEventAttributes;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends FormRequest
{
    use ValidatesEventAttributes;

    public function authorize(): bool
    {
        /** @var Event $event */
        $event = $this->route('event');

        return $this->user()->can('update', $event);
    }

    protected function prepareForValidation(): void
    {
        $this->prepareEventScheduleValidation();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->eventAttributeRules();
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateEventSchedule($validator);
    }
}
