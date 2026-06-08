<?php

namespace App\Http\Middleware;

use App\Models\DeviceChangeRequest;
use App\Models\User;
use App\Support\Inertia\AdminPermissions;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'can' => AdminPermissions::for($request->user()),
                'pending_device_change_requests_count' => self::pendingDeviceChangeRequestsCount($request),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private static function pendingDeviceChangeRequestsCount(Request $request): int
    {
        $user = $request->user();

        if ($user === null || ! $user->can('viewAny', User::class)) {
            return 0;
        }

        return DeviceChangeRequest::query()->pending()->count();
    }
}
