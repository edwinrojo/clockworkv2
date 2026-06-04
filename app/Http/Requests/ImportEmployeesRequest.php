<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportEmployeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dry_run' => $this->boolean('dry_run'),
            'update_existing' => $this->boolean('update_existing'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'dry_run' => ['boolean'],
            'update_existing' => ['boolean'],
            'mode' => ['nullable', Rule::in(['preview', 'import'])],
        ];
    }
}
