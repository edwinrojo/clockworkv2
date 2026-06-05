<?php

namespace Tests\Unit\Admin;

use App\Models\Department;
use App\Models\User;
use App\Services\Admin\EmployeeNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_sequential_numbers_per_department(): void
    {
        $department = Department::factory()->create(['code' => 'HR']);

        User::factory()->employee()->create([
            'department_id' => $department->id,
            'employee_number' => 'HR-00003',
        ]);

        $generator = new EmployeeNumberGenerator;

        $this->assertSame('HR-00004', $generator->nextFor($department));
        $this->assertSame('HR-00005', $generator->nextFor($department));
    }

    public function test_departments_with_different_codes_have_independent_sequences(): void
    {
        $hr = Department::factory()->create(['code' => 'HR']);
        $fin = Department::factory()->create(['code' => 'FIN']);

        $generator = new EmployeeNumberGenerator;

        $this->assertSame('HR-00001', $generator->nextFor($hr));
        $this->assertSame('FIN-00001', $generator->nextFor($fin));
    }
}
