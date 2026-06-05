<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportEmployeesRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\Admin\EmployeeImportService;
use App\Support\Admin\ActivityLogger;
use App\Support\Admin\UserFormOptions;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserImportController extends Controller
{
    public function __construct(private EmployeeImportService $employeeImport) {}

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/Import', [
            ...UserFormOptions::for(request()->user()),
            'requiredColumns' => [
                'email',
                'first_name',
                'last_name',
                'id_number',
            ],
            'optionalColumns' => ['middle_name', 'suffix'],
            'importResult' => session('importResult'),
        ]);
    }

    public function store(ImportEmployeesRequest $request): RedirectResponse
    {
        $department = Department::query()->findOrFail($request->validated('department_id'));
        $dryRun = $request->boolean('dry_run') || $request->input('mode') === 'preview';

        $result = $this->employeeImport->process(
            $request->file('file'),
            $department,
            dryRun: $dryRun,
            updateExisting: $request->boolean('update_existing'),
        );

        if (! $dryRun) {
            ActivityLogger::log($request, 'employee_import', null, [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'created' => $result['created'],
                'updated' => $result['updated'],
                'failed' => count($result['failed']),
            ]);
        }

        $message = $dryRun
            ? __('Preview for :department: :create create, :update update, :failed failed.', [
                'department' => $department->name,
                'create' => $result['created'],
                'update' => $result['updated'],
                'failed' => count($result['failed']),
            ])
            : __(':department: :create created, :update updated.', [
                'department' => $department->name,
                'create' => $result['created'],
                'update' => $result['updated'],
            ]);

        if ($result['failed'] !== []) {
            $message .= ' '.__(':failed row(s) failed.', ['failed' => count($result['failed'])]);
        }

        Inertia::flash('toast', [
            'type' => $result['failed'] === [] ? 'success' : 'warning',
            'message' => $message,
        ]);

        return redirect()
            ->route('users.import.create')
            ->with('importResult', $result);
    }

    public function template(): StreamedResponse
    {
        $this->authorize('create', User::class);

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            foreach ($this->employeeImport->templateRows() as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'employee-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
