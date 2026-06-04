<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_import_employees_from_csv(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $department = Department::factory()->create(['name' => 'Human Resources']);

        $csv = implode("\n", [
            'employee_number,email,first_name,last_name,department,password',
            'EMP-90001,imported.one@clockwork.test,Maria,Santos,Human Resources,password',
            'EMP-90002,imported.two@clockwork.test,Jose,Rizal,Human Resources,password',
        ]);

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($admin)
            ->post(route('users.import.store'), ['file' => $file])
            ->assertRedirect(route('users.import.create'))
            ->assertSessionHas('importResult', fn (array $result): bool => $result['created'] === 2 && $result['failed'] === []);

        $this->assertDatabaseHas('users', [
            'email' => 'imported.one@clockwork.test',
            'employee_number' => 'EMP-90001',
            'role' => UserRole::Employee->value,
            'department_id' => $department->id,
        ]);
    }

    public function test_import_reports_row_errors_without_creating_invalid_rows(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Department::factory()->create(['name' => 'Finance']);

        $csv = implode("\n", [
            'employee_number,email,first_name,last_name,department,password',
            'EMP-90003,duplicate@clockwork.test,Ana,Reyes,Finance,password',
            'EMP-90004,duplicate@clockwork.test,Ben,Reyes,Finance,password',
        ]);

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($admin)
            ->post(route('users.import.store'), ['file' => $file])
            ->assertRedirect(route('users.import.create'));

        $result = session('importResult');

        $this->assertSame(1, $result['created']);
        $this->assertCount(1, $result['failed']);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_event_manager_can_download_import_template(): void
    {
        $manager = User::factory()->eventManager()->create();

        $this->actingAs($manager)
            ->get(route('users.import.template'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
