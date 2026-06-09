<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVenueRequest;
use App\Http\Requests\UpdateVenueRequest;
use App\Models\Venue;
use App\Support\Admin\TableFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Venue::class);

        $filters = TableFilters::fromRequest($request, ['is_active']);

        $venues = Venue::query()
            ->withCount('events')
            ->when($filters->searchLike(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('address', 'like', $search);
                });
            })
            ->when(
                $filters->extraString('is_active') !== null,
                fn ($query) => $query->where('is_active', $filters->extraString('is_active') === '1'),
            )
            ->orderBy('name')
            ->paginate($filters->perPage)
            ->withQueryString()
            ->through(fn (Venue $venue) => $this->venuePayload($venue, $request));

        return Inertia::render('venues/Index', [
            'venues' => $venues,
            'filters' => $filters->toArray(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Venue::class);

        return Inertia::render('venues/Create');
    }

    public function store(StoreVenueRequest $request): RedirectResponse
    {
        Venue::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue created.')]);

        return to_route('venues.index');
    }

    public function edit(Request $request, Venue $venue): Response
    {
        $this->authorize('update', $venue);

        $venue->loadCount('events');

        return Inertia::render('venues/Edit', [
            'venue' => $this->venuePayload($venue, $request),
        ]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        $venue->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue updated.')]);

        return to_route('venues.index');
    }

    public function destroy(Request $request, Venue $venue): RedirectResponse
    {
        $this->authorize('delete', $venue);

        if ($venue->events()->exists()) {
            return back()->withErrors([
                'venue' => __('This venue is used by events and cannot be deleted.'),
            ]);
        }

        $venue->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue deleted.')]);

        return to_route('venues.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function venuePayload(Venue $venue, Request $request): array
    {
        return [
            'id' => $venue->id,
            'name' => $venue->name,
            'address' => $venue->address,
            'latitude' => $venue->latitude,
            'longitude' => $venue->longitude,
            'geofence_radius_meters' => $venue->geofence_radius_meters,
            'geofence_polygon' => $venue->geofence_polygon,
            'accuracy_buffer_meters' => $venue->accuracy_buffer_meters,
            'is_active' => $venue->is_active,
            'events_count' => $venue->events_count ?? 0,
            'can' => [
                'update' => $request->user()?->can('update', $venue) ?? false,
                'delete' => $request->user()?->can('delete', $venue) ?? false,
            ],
        ];
    }
}
