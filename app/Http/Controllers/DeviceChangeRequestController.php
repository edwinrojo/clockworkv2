<?php

namespace App\Http\Controllers;

use App\Enums\DeviceChangeRequestStatus;
use App\Http\Requests\RejectDeviceChangeRequest;
use App\Models\DeviceChangeRequest;
use App\Services\Auth\DeviceRegistrationService;
use App\Support\Admin\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeviceChangeRequestController extends Controller
{
    public function __construct(private DeviceRegistrationService $deviceRegistration) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DeviceChangeRequest::class);

        $requests = DeviceChangeRequest::query()
            ->pending()
            ->with([
                'user:id,first_name,middle_name,last_name,suffix,email,employee_number',
                'user.employeeDevice',
            ])
            ->orderBy('created_at')
            ->get()
            ->map(fn (DeviceChangeRequest $changeRequest) => $this->requestPayload($changeRequest, $request));

        return Inertia::render('device-requests/Index', [
            'requests' => $requests,
        ]);
    }

    public function approve(Request $request, DeviceChangeRequest $deviceChangeRequest): RedirectResponse
    {
        $this->authorize('review', $deviceChangeRequest);

        if ($deviceChangeRequest->status !== DeviceChangeRequestStatus::Pending) {
            return back()->withErrors([
                'device_change' => __('This device change request has already been reviewed.'),
            ]);
        }

        $this->deviceRegistration->approve($deviceChangeRequest, $request->user());

        ActivityLogger::log($request, 'device_change_approved', $deviceChangeRequest->user, [
            'device_change_request_id' => $deviceChangeRequest->id,
            'device_name' => $deviceChangeRequest->device_name,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Device change approved for :name.', ['name' => $deviceChangeRequest->user->name]),
        ]);

        return back();
    }

    public function reject(RejectDeviceChangeRequest $request, DeviceChangeRequest $deviceChangeRequest): RedirectResponse
    {
        if ($deviceChangeRequest->status !== DeviceChangeRequestStatus::Pending) {
            return back()->withErrors([
                'device_change' => __('This device change request has already been reviewed.'),
            ]);
        }

        $this->deviceRegistration->reject(
            $deviceChangeRequest,
            $request->user(),
            $request->validated('rejection_reason'),
        );

        ActivityLogger::log($request, 'device_change_rejected', $deviceChangeRequest->user, [
            'device_change_request_id' => $deviceChangeRequest->id,
            'rejection_reason' => $request->validated('rejection_reason'),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Device change rejected for :name.', ['name' => $deviceChangeRequest->user->name]),
        ]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(DeviceChangeRequest $changeRequest, Request $request): array
    {
        $employee = $changeRequest->user;

        return [
            'id' => $changeRequest->id,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'employee_number' => $employee->employee_number,
            ],
            'current_device' => $employee->employeeDevice ? [
                'device_name' => $employee->employeeDevice->device_name,
                'device_model' => $employee->employeeDevice->device_model,
                'platform' => $employee->employeeDevice->platform,
                'last_seen_at' => $employee->employeeDevice->last_seen_at?->toIso8601String(),
            ] : null,
            'requested_device' => [
                'device_name' => $changeRequest->device_name,
                'device_model' => $changeRequest->device_model,
                'platform' => $changeRequest->platform,
                'os_version' => $changeRequest->os_version,
            ],
            'reason' => $changeRequest->reason,
            'created_at' => $changeRequest->created_at->toIso8601String(),
            'can' => [
                'review' => $request->user()?->can('review', $changeRequest) ?? false,
            ],
        ];
    }
}
