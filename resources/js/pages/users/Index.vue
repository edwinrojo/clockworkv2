<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import UserRoleBadge from '@/components/admin/UserRoleBadge.vue';
import { Button } from '@/components/ui/button';
import { create, destroy, edit, index } from '@/routes/users';
import { create as importEmployees } from '@/routes/users/import';
import type { UserRow } from '@/types/admin';

defineProps<{
    users: UserRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users', href: index() }],
    },
});

const page = usePage();
const canCreate = computed(() => page.props.auth.can.users.create);

function deleteUser(id: string): void {
    if (!confirm('Delete this user? This cannot be undone.')) {
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
                    <tr
                        v-for="managedUser in users"
                        :key="managedUser.id"
                    >
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
                            <div
                                class="flex items-center justify-end gap-2"
                            >
                                <Button
                                    v-if="managedUser.can.update"
                                    variant="outline"
                                    size="sm"
                                    as-child
                                >
                                    <Link :href="edit(managedUser.id)">
                                        Edit
                                    </Link>
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
                    <tr v-if="users.length === 0">
                        <td colspan="6" class="py-10 text-center text-muted-foreground">
                            No users found.
                        </td>
                    </tr>
                </tbody>
        </AdminTable>
    </div>
</template>
