<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\Admin\TableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ActivityLog::class);

        $filters = TableFilters::fromRequest($request, ['action']);
        $action = $filters->extraString('action');

        $logs = ActivityLog::query()
            ->with('user:id,first_name,middle_name,last_name,suffix,email')
            ->when($action !== null, fn ($query) => $query->where('action', $action))
            ->when($filters->searchLike(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('action', 'like', $search)
                        ->orWhereHas('user', function ($query) use ($search): void {
                            $query->where('email', 'like', $search)
                                ->orWhere('first_name', 'like', $search)
                                ->orWhere('last_name', 'like', $search);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate($filters->perPage)
            ->withQueryString()
            ->through(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => str($log->action)->headline()->toString(),
                'user_name' => $log->user?->name,
                'user_email' => $log->user?->email,
                'subject_type' => $log->subject_type ? class_basename($log->subject_type) : null,
                'properties' => $log->properties,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        $actions = ActivityLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return Inertia::render('audit/Index', [
            'logs' => $logs,
            'filters' => $filters->toArray(),
            'actions' => $actions,
        ]);
    }
}
