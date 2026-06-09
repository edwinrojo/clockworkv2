<?php

namespace App\Support\Admin;

use Illuminate\Http\Request;

class TableFilters
{
    public const DEFAULT_PER_PAGE = 15;

    /**
     * @param  array<string, string|null>  $extra
     */
    public function __construct(
        public readonly ?string $search,
        public readonly int $perPage,
        public readonly array $extra = [],
    ) {}

    /**
     * @param  list<string>  $extraKeys
     */
    public static function fromRequest(Request $request, array $extraKeys = []): self
    {
        $search = $request->string('search')->toString();

        $extra = [];
        foreach ($extraKeys as $key) {
            $value = $request->string($key)->toString();
            $extra[$key] = $value !== '' ? $value : null;
        }

        return new self(
            search: $search !== '' ? $search : null,
            perPage: min(100, max(5, $request->integer('per_page', self::DEFAULT_PER_PAGE))),
            extra: $extra,
        );
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'per_page' => $this->perPage,
            ...$this->extra,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public function searchLike(): ?string
    {
        return $this->search === null ? null : '%'.$this->search.'%';
    }

    public function extraString(string $key): ?string
    {
        $value = $this->extra[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
