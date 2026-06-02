<?php

namespace App\Support\Admin;

use App\Enums\DuplicatePolicy;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Venue;
use BackedEnum;

class EventFormOptions
{
    /**
     * @return array{
     *     venues: list<array{id: string, name: string}>,
     *     types: list<array{value: string, label: string}>,
     *     statuses: list<array{value: string, label: string}>,
     *     duplicatePolicies: list<array{value: string, label: string}>
     * }
     */
    public static function all(): array
    {
        return [
            'venues' => Venue::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Venue $venue) => [
                    'id' => $venue->id,
                    'name' => $venue->name,
                ])
                ->all(),
            'types' => self::enumOptions(EventType::cases()),
            'statuses' => self::enumOptions(EventStatus::cases()),
            'duplicatePolicies' => self::enumOptions(DuplicatePolicy::cases()),
        ];
    }

    /**
     * @param  list<BackedEnum>  $cases
     * @return list<array{value: string, label: string}>
     */
    private static function enumOptions(array $cases): array
    {
        return array_map(
            fn (BackedEnum $case) => [
                'value' => $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : $case->name,
            ],
            $cases,
        );
    }
}
