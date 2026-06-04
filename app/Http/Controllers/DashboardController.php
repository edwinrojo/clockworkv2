<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    public function __invoke(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        return Inertia::render('Dashboard', $this->dashboard->snapshot());
    }
}
