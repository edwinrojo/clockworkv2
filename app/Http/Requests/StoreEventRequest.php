<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesEventAttributes;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    use ValidatesEventAttributes;

    public function authorize(): bool
    {
        return $this->user()->can('create', Event::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->eventAttributeRules();
    }
}
