<?php

namespace App\Enums;

enum EventRosterScope: string
{
    case AllActiveEmployees = 'all_active';
    case Departments = 'departments';
    case Employees = 'employees';

    public function label(): string
    {
        return match ($this) {
            self::AllActiveEmployees => 'All active employees',
            self::Departments => 'Selected departments',
            self::Employees => 'Selected employees',
        };
    }
}
