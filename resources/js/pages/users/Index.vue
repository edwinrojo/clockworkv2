<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import AdminTableFilters from '@/components/admin/AdminTableFilters.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import UserRoleBadge from '@/components/admin/UserRoleBadge.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { create, destroy, edit, index } from '@/routes/users';
import { create as importEmployees } from '@/routes/users/import';
import { confirm } from '@/lib/confirm';
import type {
    DepartmentOption,
    Paginated,
    SelectOption,
    TableFilters,
    UserRow,
} from '@/types/admin';

defineProps<{
    users: Paginated<UserRow>;
    filters: TableFilters;
    roles: SelectOption[];
    departments: DepartmentOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: index() }],
    },
});

const page = usePage();
const canCreate = computed(() => page.props.auth.can.users.create);

async function deleteUser(id: string): Promise<void> {
    const confirmed = await confirm({
        title: 'Delete this user?',
        description: 'This cannot be undone.',
        confirmLabel: 'Delete',
        variant: 'destructive',
    });

    if (!confirmed) {
        return;
    }

    router.delete(destroy.url(id));
}
</script>

<template>
    <Head title="Users" />

    <div class="admin-page">
        <AdminPageHeader
            title="Users"
            description="Manage employee and admin accounts"
            :create-href="canCreate ? create() : undefined"
            create-label="Add user"
        >
            <template v-if="canCreate" #actions>
                <Button variant="outline" as-child>
                    <Link :href="importEmployees()">Import CSV</Link>
                </Button>
            </template>
        </AdminPageHeader>

        <AdminTableFilters
            :action="index()"
            :filters="filters"
            search-placeholder="Name, email, or employee #"
        >
            <div class="grid gap-2">
                <Label for="role">Role</Label>
                <select
                    id="role"
                    name="role"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                >
                    <option value="">All roles</option>
                    <option
                        v-for="role in roles"
                        :key="role.value"
                        :value="role.value"
                        :selected="filters.role === role.value"
                    >
                        {{ role.label }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="department_id">Department</Label>
                <select
                    id="department_id"
                    name="department_id"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                >
                    <option value="">All departments</option>
                    <option
                        v-for="department in departments"
                        :key="department.id"
                        :value="department.id"
                        :selected="filters.department_id === department.id"
                    >
                        {{ department.name }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="is_active">Status</Label>
                <select
                    id="is_active"
                    name="is_active"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                >
                    <option value="">All</option>
                    <option value="1" :selected="filters.is_active === '1'">
                        Active
                    </option>
                    <option value="0" :selected="filters.is_active === '0'">
                        Inactive
                    </option>
                </select>
            </div>
        </AdminTableFilters>

        <AdminTable>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="managedUser in users.data" :key="managedUser.id">
                    <td>
                        <div class="font-medium">
                            {{ managedUser.name }}
                        </div>
                        <div
                            v-if="managedUser.employee_number"
                            class="text-muted-foreground"
                        >
                            {{ managedUser.employee_number }}
                        </div>
                    </td>
                    <td class="text-muted-foreground">
                        {{ managedUser.email }}
                    </td>
                    <td>
                        <UserRoleBadge
                            :role="managedUser.role"
                            :label="managedUser.role_label"
                        />
                    </td>
                    <td class="text-muted-foreground">
                        {{ managedUser.department_name ?? '—' }}
                    </td>
                    <td>
                        <StatusBadge :active="managedUser.is_active" />
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-2">
                            <Button
                                v-if="managedUser.can.update"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="edit(managedUser.id)">Edit</Link>
                            </Button>
                            <Button
                                v-if="managedUser.can.delete"
                                variant="destructive"
                                size="sm"
                                type="button"
                                @click="deleteUser(managedUser.id)"
                            >
                                Delete
                            </Button>
                        </div>
                    </td>
                </tr>
                <tr v-if="users.data.length === 0">
                    <td
                        colspan="6"
                        class="py-10 text-center text-muted-foreground"
                    >
                        No users found.
                    </td>
                </tr>
            </tbody>
            <template #footer>
                <AdminPagination :paginator="users" />
            </template>
        </AdminTable>
    </div>
</template>
