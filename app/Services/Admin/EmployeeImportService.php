<?php

namespace App\Services\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Services\Auth\EmployeeEmailVerificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeImportService
{
    private const int MaxRows = 500;

    /**
     * @var list<string>
     */
    private const array RequiredHeaders = [
        'email',
        'first_name',
        'last_name',
        'id_number',
    ];

    public function __construct(
        private EmployeeNumberGenerator $employeeNumbers,
        private EmployeeEmailVerificationService $emailVerification,
    ) {}

    /**
     * @return array{
     *     created: int,
     *     updated: int,
     *     failed: list<array{row: int, messages: list<string>}>,
     *     preview: list<array{row: int, action: string, email: string, employee_number: string}>
     * }
     */
    public function process(
        UploadedFile $file,
        Department $department,
        bool $dryRun = false,
        bool $updateExisting = false,
    ): array {
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

        $created = 0;
        $updated = 0;
        $failed = [];
        $preview = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $result = $this->processRow($row, $department, $dryRun, $updateExisting);

                if ($result['action'] === 'create') {
                    $created++;
                } else {
                    $updated++;
                }

                if ($dryRun) {
                    $preview[] = [
                        'row' => $rowNumber,
                        'action' => $result['action'],
                        'email' => (string) ($row['email'] ?? ''),
                        'employee_number' => $result['employee_number'],
                    ];
                }
            } catch (ValidationException $exception) {
                $failed[] = [
                    'row' => $rowNumber,
                    'messages' => collect($exception->errors())->flatten()->all(),
                ];
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'preview' => $preview,
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
            'id number', 'id_no', 'id no' => 'id_number',
            'firstname', 'first name' => 'first_name',
            'middlename', 'middle name' => 'middle_name',
            'lastname', 'last name' => 'last_name',
            default => str_replace(' ', '_', $column),
        };
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array{action: string, employee_number: string}
     */
    private function processRow(
        array $row,
        Department $department,
        bool $dryRun,
        bool $updateExisting,
    ): array {
        $existing = User::query()
            ->where('role', UserRole::Employee)
            ->where('email', $row['email'] ?? '')
            ->first();

        if ($existing !== null && $existing->department_id !== $department->id) {
            throw ValidationException::withMessages([
                'email' => [__('This email belongs to an employee in another department.')],
            ]);
        }

        if ($existing !== null && ! $updateExisting) {
            throw ValidationException::withMessages([
                'email' => [__('Employee already exists. Enable update existing to modify this row.')],
            ]);
        }

        $employeeNumber = $existing?->employee_number ?? $this->employeeNumbers->nextFor($department);

        $validator = Validator::make(
            [
                'email' => $row['email'] ?? null,
                'first_name' => $row['first_name'] ?? null,
                'middle_name' => $row['middle_name'] ?? null,
                'last_name' => $row['last_name'] ?? null,
                'suffix' => $row['suffix'] ?? null,
                'id_number' => $row['id_number'] ?? null,
                'is_active' => true,
                'department_id' => $department->id,
            ],
            $this->rulesForRow($existing?->id),
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        if ($dryRun) {
            return [
                'action' => $existing !== null ? 'update' : 'create',
                'employee_number' => $employeeNumber,
            ];
        }

        $attributes = [
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'],
            'last_name' => $data['last_name'],
            'suffix' => $data['suffix'],
            'password' => Hash::make($data['id_number']),
            'department_id' => $department->id,
            'is_active' => $data['is_active'],
        ];

        if ($existing !== null) {
            $previousEmail = $existing->email;

            $existing->update($attributes);

            if ($existing->email !== $previousEmail) {
                $existing->forceFill(['email_verified_at' => null])->save();
            }

            if (! $existing->hasVerifiedEmail()) {
                $this->emailVerification->sendCode($existing->refresh());
            }

            return [
                'action' => 'update',
                'employee_number' => (string) $existing->employee_number,
            ];
        }

        $user = User::query()->create([
            ...$attributes,
            'employee_number' => $employeeNumber,
            'role' => UserRole::Employee,
            'email_verified_at' => null,
        ]);

        $this->emailVerification->sendCode($user);

        return [
            'action' => 'create',
            'employee_number' => $employeeNumber,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesForRow(?string $userId = null): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                $userId === null
                    ? Rule::unique('users', 'email')
                    : Rule::unique('users', 'email')->ignore($userId),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'id_number' => ['required', 'string', 'min:4', 'max:50'],
            'is_active' => ['boolean'],
            'department_id' => ['required', 'ulid', Rule::exists('departments', 'id')],
        ];
    }

    /**
     * @return list<list<string>>
     */
    public function templateRows(): array
    {
        return [
            [
                'email',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'id_number',
            ],
            [
                'juan.delacruz@example.gov.ph',
                'Juan',
                'Santos',
                'Dela Cruz',
                '',
                '1234567890',
            ],
        ];
    }
}
