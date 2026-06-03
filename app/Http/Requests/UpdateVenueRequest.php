<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesVenueGeofence;
use App\Models\Venue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVenueRequest extends FormRequest
{
    use ValidatesVenueGeofence;

    public function authorize(): bool
    {
        /** @var Venue $venue */
        $venue = $this->route('venue');

        return $this->user()->can('update', $venue);
    }

    protected function prepareForValidation(): void
    {
        $this->prepareVenueGeofence();

        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:10', 'max:50000'],
            'accuracy_buffer_meters' => ['required', 'integer', 'min:0', 'max:500'],
            'is_active' => ['boolean'],
            ...$this->venueGeofenceRules(),
        ];
    }
}
