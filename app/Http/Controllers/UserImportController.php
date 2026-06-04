<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportEmployeesRequest;
use App\Models\User;
use App\Services\Admin\EmployeeImportService;
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
            'requiredColumns' => [
                'employee_number',
                'email',
                'first_name',
                'last_name',
                'department',
                'password',
            ],
            'optionalColumns' => ['middle_name', 'suffix', 'is_active'],
            'importResult' => session('importResult'),
        ]);
    }

    public function store(ImportEmployeesRequest $request): RedirectResponse
    {
        $result = $this->employeeImport->import($request->file('file'));

        $message = __(':count employee(s) imported.', ['count' => $result['created']]);

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
