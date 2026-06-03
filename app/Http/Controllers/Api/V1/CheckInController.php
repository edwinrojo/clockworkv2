<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CheckInException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckInRequest;
use App\Http\Resources\Api\V1\AttendanceResource;
use App\Services\Attendance\CheckInService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class CheckInController extends Controller
{
    public function __construct(private CheckInService $checkInService) {}

    public function store(CheckInRequest $request): JsonResponse
    {
        try {
            $result = $this->checkInService->checkIn(
                user: $request->user(),
                qrToken: $request->string('qr_token')->toString(),
                latitude: (float) $request->input('latitude'),
                longitude: (float) $request->input('longitude'),
                accuracyMeters: $request->filled('accuracy') ? (float) $request->input('accuracy') : null,
                gpsCapturedAt: $request->date('captured_at'),
                idempotencyKey: $request->string('idempotency_key')->toString() ?: null,
            );
        } catch (CheckInException $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                422,
                $exception->errorCode->value,
            );
        }

        return ApiResponse::success([
            'attendance' => new AttendanceResource($result['attendance']),
            'replayed' => $result['replayed'],
        ], $result['replayed'] ? 200 : 201);
    }
}
