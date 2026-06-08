<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
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

    <div class="flex flex-col gap-6 p-4">
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

        <div
            class="admin-panel"
        >
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">Department</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="managedUser in users"
                        :key="managedUser.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
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
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ managedUser.email }}
                        </td>
                        <td class="px-4 py-3">
                            <UserRoleBadge
                                :role="managedUser.role"
                                :label="managedUser.role_label"
                            />
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ managedUser.department_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :active="managedUser.is_active" />
                        </td>
                        <td class="px-4 py-3">
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
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No users found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
