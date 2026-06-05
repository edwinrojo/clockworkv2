<?php

namespace App\Services\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Str;

class EmployeeNumberGenerator
{
    /** @var array<string, int> */
    private array $sequenceByDepartment = [];

    public function nextFor(Department $department): string
    {
        $prefix = $this->prefixFor($department);
        $sequence = $this->nextSequence($department->id, $prefix);

        return sprintf('%s-%05d', $prefix, $sequence);
    }

    private function prefixFor(Department $department): string
    {
        if (filled($department->code)) {
            return Str::upper(Str::squish((string) $department->code));
        }

        $slug = Str::upper(Str::slug($department->name, ''));

        return $slug !== '' ? Str::substr($slug, 0, 12) : 'EMP';
    }

    private function nextSequence(string $departmentId, string $prefix): int
    {
        if (! isset($this->sequenceByDepartment[$departmentId])) {
            $this->sequenceByDepartment[$departmentId] = $this->highestSequenceInDepartment($departmentId, $prefix);
        }

        $this->sequenceByDepartment[$departmentId]++;

        return $this->sequenceByDepartment[$departmentId];
    }

    private function highestSequenceInDepartment(string $departmentId, string $prefix): int
    {
        $pattern = $prefix.'-%';
        $highest = 0;

        $numbers = User::query()
            ->where('department_id', $departmentId)
            ->where('role', UserRole::Employee)
            ->where('employee_number', 'like', $pattern)
            ->pluck('employee_number');

        foreach ($numbers as $employeeNumber) {
            if (! is_string($employeeNumber)) {
                continue;
            }

            if (! preg_match('/-(\d+)$/', $employeeNumber, $matches)) {
                continue;
            }

            $highest = max($highest, (int) $matches[1]);
        }

        return $highest;
    }
}
