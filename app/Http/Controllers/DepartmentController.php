<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::query()
            ->with('parent:id,name')
            ->withCount(['users', 'children'])
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department) => $this->departmentPayload($department, $request));

        return Inertia::render('departments/Index', [
            'departments' => $departments,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Department::class);

        return Inertia::render('departments/Create', [
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department created.')]);

        return to_route('departments.index');
    }

    public function edit(Request $request, Department $department): Response
    {
        $this->authorize('update', $department);

        $department->load('parent:id,name')->loadCount(['users', 'children']);

        return Inertia::render('departments/Edit', [
            'department' => $this->departmentPayload($department, $request),
            'parents' => $this->parentOptions($department),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department updated.')]);

        return to_route('departments.index');
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        if ($department->users()->exists()) {
            return back()->withErrors([
                'department' => __('This department has employees assigned and cannot be deleted.'),
            ]);
        }

        if ($department->children()->exists()) {
            return back()->withErrors([
                'department' => __('This department has child offices and cannot be deleted.'),
            ]);
        }

        $department->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department deleted.')]);

        return to_route('departments.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function departmentPayload(Department $department, Request $request): array
    {
        return [
            'id' => $department->id,
            'name' => $department->name,
            'code' => $department->code,
            'parent_id' => $department->parent_id,
            'parent_name' => $department->parent?->name,
            'is_active' => $department->is_active,
            'users_count' => $department->users_count ?? 0,
            'children_count' => $department->children_count ?? 0,
            'can' => [
                'update' => $request->user()?->can('update', $department) ?? false,
                'delete' => $request->user()?->can('delete', $department) ?? false,
            ],
        ];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function parentOptions(?Department $except = null): array
    {
        return Department::query()
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
            ])
            ->all();
    }
}
