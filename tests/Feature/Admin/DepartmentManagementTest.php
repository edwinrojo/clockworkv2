<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_a_department(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->post(route('departments.store'), [
            'name' => 'Provincial Accountant Office',
            'code' => 'PAO',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('departments.index'));

        $this->assertDatabaseHas('departments', [
            'name' => 'Provincial Accountant Office',
            'code' => 'PAO',
        ]);
    }

    public function test_viewer_cannot_create_a_department(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)
            ->get(route('departments.create'))
            ->assertForbidden();
    }

    public function test_viewer_can_list_departments(): void
    {
        $viewer = User::factory()->viewer()->create();
        Department::factory()->create(['name' => 'HRMO']);

        $this->actingAs($viewer)
            ->get(route('departments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('departments/Index')
                ->has('departments', 1)
            );
    }

    public function test_employee_cannot_list_departments(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)
            ->get(route('departments.index'))
            ->assertForbidden();
    }

    public function test_department_with_users_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $department = Department::factory()->create();
        User::factory()->employee()->forDepartment($department)->create();

        $this->actingAs($admin)
            ->delete(route('departments.destroy', $department))
            ->assertRedirect()
            ->assertSessionHasErrors('department');

        $this->assertModelExists($department);
    }
}
