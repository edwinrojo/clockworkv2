<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AttendanceResource;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 50);

        $attendances = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->with(['event.venue'])
            ->orderByDesc('checked_in_at')
            ->paginate($perPage);

        return response()->json([
            'data' => [
                'attendances' => AttendanceResource::collection($attendances->items()),
            ],
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }
}
