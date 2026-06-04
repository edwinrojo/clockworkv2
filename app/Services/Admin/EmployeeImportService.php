<?php

namespace App\Services\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class EmployeeImportService
{
    private const int MaxRows = 500;

    /**
     * @var list<string>
     */
    private const array RequiredHeaders = [
        'employee_number',
        'email',
        'first_name',
        'last_name',
        'department',
        'password',
    ];

    /**
     * @return array{
     *     created: int,
     *     failed: list<array{row: int, messages: list<string>}>
     * }
     */
    public function import(UploadedFile $file): array
    {
        $rows = $this->parseCsv($file);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => [__('The CSV file is empty or has no data rows.')],
            ]);
        }

        if (count($rows) > self::MaxRows) {
            throw ValidationException::withMessages([
                'file' => [__('Import is limited to :max rows per file.', ['max' => self::MaxRows])],
            ]);
        }

        $departments = Department::query()
            ->get(['id', 'name'])
            ->keyBy(fn (Department $department): string => Str::lower(trim($department->name)));

        $created = 0;
        $failed = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $this->createEmployee($row, $departments);
                $created++;
            } catch (ValidationException $exception) {
                $failed[] = [
                    'row' => $rowNumber,
                    'messages' => collect($exception->errors())->flatten()->all(),
                ];
            }
        }

        return [
            'created' => $created,
            'failed' => $failed,
        ];
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => [__('Unable to read the uploaded file.')],
            ]);
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return [];
        }

        $header = array_map(fn (?string $column): string => $this->normalizeHeader($column ?? ''), $header);
        $this->assertValidHeader($header);

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($this->isBlankRow($data)) {
                continue;
            }

            $row = [];

            foreach ($header as $index => $column) {
                $row[$column] = isset($data[$index]) ? trim((string) $data[$index]) : null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn (?string $value): bool => blank($value));
    }

    /**
     * @param  list<string>  $header
     */
    private function assertValidHeader(array $header): void
    {
        $missing = array_diff(self::RequiredHeaders, $header);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => [
                    __('Missing required CSV columns: :columns', [
                        'columns' => implode(', ', $missing),
                    ]),
                ],
            ]);
        }
    }

    private function normalizeHeader(string $column): string
    {
        $column = Str::lower(trim($column));

        return match ($column) {
            'department_name' => 'department',
            default => $column,
        };
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  Collection<string, Department>  $departments
     */
    private function createEmployee(array $row, $departments): void
    {
        $departmentKey = Str::lower(trim((string) ($row['department'] ?? '')));
        $department = $departments->get($departmentKey);

        $validator = Validator::make(
            [
                'employee_number' => $row['employee_number'] ?? null,
                'email' => $row['email'] ?? null,
                'first_name' => $row['first_name'] ?? null,
                'middle_name' => $row['middle_name'] ?? null,
                'last_name' => $row['last_name'] ?? null,
                'suffix' => $row['suffix'] ?? null,
                'password' => $row['password'] ?? null,
                'is_active' => $this->parseBoolean($row['is_active'] ?? '1'),
                'department_id' => $department?->id,
            ],
            [
                'employee_number' => ['required', 'string', 'max:50', Rule::unique('users', 'employee_number')],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
                'first_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'suffix' => ['nullable', 'string', 'max:20'],
                'password' => ['required', 'string', Password::default()],
                'is_active' => ['boolean'],
                'department_id' => ['required', 'ulid', Rule::exists('departments', 'id')],
            ],
            [
                'department_id.required' => __('Unknown department ":name".', [
                    'name' => $row['department'] ?? '',
                ]),
            ],
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        User::query()->create([
            'employee_number' => $data['employee_number'],
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'],
            'last_name' => $data['last_name'],
            'suffix' => $data['suffix'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Employee,
            'department_id' => $data['department_id'],
            'is_active' => $data['is_active'],
        ]);
    }

    private function parseBoolean(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return in_array(Str::lower(trim($value)), ['1', 'true', 'yes', 'y'], true);
    }

    /**
     * @return list<list<string>>
     */
    public function templateRows(): array
    {
        return [
            [
                'employee_number',
                'email',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'department',
                'password',
                'is_active',
            ],
            [
                'EMP-10001',
                'juan.delacruz@example.gov.ph',
                'Juan',
                '',
                'Dela Cruz',
                '',
                'Human Resources',
                'ChangeMe123!',
                '1',
            ],
        ];
    }
}
