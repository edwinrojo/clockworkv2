<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Notifications\EmployeeEmailVerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_import_employees_for_a_department(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        $department = Department::factory()->create([
            'name' => 'Human Resources',
            'code' => 'HR',
        ]);

        $csv = implode("\n", [
            'email,first_name,middle_name,last_name,suffix,id_number',
            'imported.one@clockwork.test,Maria,Santos,Delacruz,,1234567890',
            'imported.two@clockwork.test,Jose,,Rizal,,9876543210',
        ]);

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($admin)
            ->post(route('users.import.store'), [
                'department_id' => $department->id,
                'file' => $file,
            ])
            ->assertRedirect(route('users.import.create'))
            ->assertSessionHas('importResult', fn (array $result): bool => $result['created'] === 2 && $result['failed'] === []);

        $this->assertDatabaseHas('users', [
            'email' => 'imported.one@clockwork.test',
            'employee_number' => 'HR-00001',
            'role' => UserRole::Employee->value,
            'department_id' => $department->id,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'imported.two@clockwork.test',
            'employee_number' => 'HR-00002',
        ]);

        $employee = User::query()->where('email', 'imported.one@clockwork.test')->first();
        $this->assertNotNull($employee);
        $this->assertTrue(Hash::check('1234567890', (string) $employee->password));
        $this->assertNull($employee->email_verified_at);

        Notification::assertSentTo($employee, EmployeeEmailVerificationCode::class);
        Notification::assertSentTo(
            User::query()->where('email', 'imported.two@clockwork.test')->first(),
            EmployeeEmailVerificationCode::class,
        );
    }

    public function test_import_requires_department_selection(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Department::factory()->create(['name' => 'Finance', 'code' => 'FIN']);

        $csv = implode("\n", [
            'email,first_name,last_name,id_number',
            'missing.dept@clockwork.test,Ana,Reyes,1111111111',
        ]);

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($admin)
            ->post(route('users.import.store'), ['file' => $file])
            ->assertSessionHasErrors('department_id');
    }

    public function test_import_reports_row_errors_without_creating_invalid_rows(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $department = Department::factory()->create(['name' => 'Finance', 'code' => 'FIN']);

        $csv = implode("\n", [
            'email,first_name,last_name,id_number',
            'duplicate@clockwork.test,Ana,Reyes,1111111111',
            'duplicate@clockwork.test,Ben,Reyes,2222222222',
        ]);

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($admin)
            ->post(route('users.import.store'), [
                'department_id' => $department->id,
                'file' => $file,
            ])
            ->assertRedirect(route('users.import.create'));

        $result = session('importResult');

        $this->assertSame(1, $result['created']);
        $this->assertCount(1, $result['failed']);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_import_rejects_email_registered_in_another_department(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $hr = Department::factory()->create(['name' => 'Human Resources', 'code' => 'HR']);
        $finance = Department::factory()->create(['name' => 'Finance', 'code' => 'FIN']);

        User::factory()->employee()->create([
            'email' => 'cross.dept@clockwork.test',
            'department_id' => $hr->id,
        ]);

        $csv = implode("\n", [
            'email,first_name,last_name,id_number',
            'cross.dept@clockwork.test,Ana,Reyes,3333333333',
        ]);

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($admin)
            ->post(route('users.import.store'), [
                'department_id' => $finance->id,
                'file' => $file,
            ])
            ->assertRedirect(route('users.import.create'));

        $result = session('importResult');

        $this->assertSame(0, $result['created']);
        $this->assertCount(1, $result['failed']);
    }

    public function test_import_can_update_existing_employees_when_enabled(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $department = Department::factory()->create(['name' => 'Human Resources', 'code' => 'HR']);
        $employee = User::factory()->employee()->create([
            'employee_number' => 'HR-00010',
            'email' => 'existing@clockwork.test',
            'first_name' => 'Old',
            'last_name' => 'Name',
            'department_id' => $department->id,
        ]);

        $csv = implode("\n", [
            'email,first_name,last_name,id_number',
            'existing@clockwork.test,New,Name,5555555555',
        ]);

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($admin)
            ->post(route('users.import.store'), [
                'department_id' => $department->id,
                'file' => $file,
                'update_existing' => '1',
            ])
            ->assertRedirect(route('users.import.create'));

        $employee->refresh();

        $this->assertSame('New', $employee->first_name);
        $this->assertSame('Name', $employee->last_name);
        $this->assertSame('HR-00010', $employee->employee_number);
        $this->assertTrue(Hash::check('5555555555', (string) $employee->password));
    }

    public function test_dry_run_does_not_persist_employees(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $department = Department::factory()->create(['name' => 'Human Resources', 'code' => 'HR']);

        $csv = implode("\n", [
            'email,first_name,last_name,id_number',
            'dryrun@clockwork.test,Ana,Reyes,4444444444',
        ]);

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csv);

        $this->actingAs($admin)
            ->post(route('users.import.store'), [
                'department_id' => $department->id,
                'file' => $file,
                'dry_run' => '1',
            ])
            ->assertRedirect(route('users.import.create'));

        $this->assertDatabaseMissing('users', [
            'email' => 'dryrun@clockwork.test',
        ]);

        $result = session('importResult');

        $this->assertSame(1, $result['created']);
        $this->assertSame('HR-00001', $result['preview'][0]['employee_number']);
    }
}
