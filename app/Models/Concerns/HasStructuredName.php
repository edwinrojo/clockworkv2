<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 *
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string|null $suffix
 */
trait HasStructuredName
{
    /**
     * @return Attribute<string, array<string, string|null>>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->fullName(),
            set: fn (?string $value): array => $this->parseNameToAttributes($value),
        );
    }

    public function fullName(): string
    {
        return collect([
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
        ])
            ->map(fn (string $column): mixed => $this->getAttribute($column))
            ->filter(fn (mixed $part): bool => filled($part))
            ->map(fn (mixed $part): string => (string) $part)
            ->implode(' ');
    }

    /**
     * @return array{first_name: string, middle_name: string|null, last_name: string, suffix: string|null}
     */
    protected function parseNameToAttributes(?string $value): array
    {
        $parts = preg_split('/\s+/', trim((string) $value)) ?: [];

        if ($parts === []) {
            return [
                'first_name' => '',
                'middle_name' => null,
                'last_name' => '',
                'suffix' => null,
            ];
        }

        $suffix = null;

        $lastPart = $parts[array_key_last($parts)];

        if (is_string($lastPart) && preg_match('/^(Jr\.?|Sr\.?|III|IV|II)$/i', $lastPart)) {
            $suffix = $lastPart;
            array_pop($parts);
        }

        $firstName = array_shift($parts) ?? '';
        $lastName = $parts !== [] ? array_pop($parts) : '';
        $middleName = $parts !== [] ? implode(' ', $parts) : null;

        return [
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'suffix' => $suffix,
        ];
    }
}
