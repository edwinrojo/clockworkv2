<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_an_employee(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $department = Department::factory()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'email' => 'ana.reyes@clockwork.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::Employee->value,
            'employee_number' => 'EMP-10002',
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'ana.reyes@clockwork.test',
            'employee_number' => 'EMP-10002',
            'role' => UserRole::Employee->value,
        ]);
    }

    public function test_event_manager_cannot_assign_super_admin_role(): void
    {
        $manager = User::factory()->eventManager()->create();

        $this->actingAs($manager)
            ->post(route('users.store'), [
                'first_name' => 'Bad',
                'last_name' => 'Actor',
                'email' => 'bad.actor@clockwork.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::SuperAdmin->value,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_event_manager_can_create_an_employee(): void
    {
        $manager = User::factory()->eventManager()->create();
        $department = Department::factory()->create();

        $this->actingAs($manager)
            ->post(route('users.store'), [
                'first_name' => 'Pedro',
                'last_name' => 'Garcia',
                'email' => 'pedro.garcia@clockwork.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::Employee->value,
                'employee_number' => 'EMP-10003',
                'department_id' => $department->id,
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));
    }

    public function test_event_manager_cannot_edit_super_admin(): void
    {
        $manager = User::factory()->eventManager()->create();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($manager)
            ->get(route('users.edit', $admin))
            ->assertForbidden();
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertForbidden();
    }

    public function test_viewer_can_list_users_but_cannot_create(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('users/Index'));

        $this->actingAs($viewer)
            ->get(route('users.create'))
            ->assertForbidden();
    }
}
