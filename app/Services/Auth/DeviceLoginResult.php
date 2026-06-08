<?php

namespace App\Services\Auth;

readonly class DeviceLoginResult
{
    private function __construct(
        public bool $allowed,
        public ?string $deviceChangeRequestId = null,
        public bool $pending = false,
    ) {}

    public static function allowed(): self
    {
        return new self(allowed: true);
    }

    public static function changeRequired(string $requestId): self
    {
        return new self(allowed: false, deviceChangeRequestId: $requestId, pending: false);
    }

    public static function changePending(string $requestId): self
    {
        return new self(allowed: false, deviceChangeRequestId: $requestId, pending: true);
    }
}
