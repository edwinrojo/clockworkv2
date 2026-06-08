<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { create, destroy, edit, index } from '@/routes/departments';
import type { DepartmentRow } from '@/types';

defineProps<{
    departments: DepartmentRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Departments', href: index() }],
    },
});

const page = usePage();
const canCreate = computed(() => page.props.auth.can.departments.create);

function deleteDepartment(id: string): void {
    if (!confirm('Delete this department? This cannot be undone.')) {
        return;
    }

    router.delete(destroy.url(id));
}
</script>

<template>
    <Head title="Departments" />

    <div class="admin-page">
        <AdminPageHeader
            title="Departments"
            description="Manage provincial offices and divisions"
            :create-href="canCreate ? create() : undefined"
            create-label="Add department"
        />

        <AdminTable>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Parent</th>
                    <th>Employees</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="department in departments"
                    :key="department.id"
                >
                    <td class="font-medium">{{ department.name }}</td>
                    <td class="text-muted-foreground">
                        {{ department.code ?? '—' }}
                    </td>
                    <td class="text-muted-foreground">
                        {{ department.parent_name ?? '—' }}
                    </td>
                    <td>{{ department.users_count }}</td>
                    <td>
                        <StatusBadge :active="department.is_active" />
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-2">
                            <Button
                                v-if="department.can.update"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="edit(department.id)">Edit</Link>
                            </Button>
                            <Button
                                v-if="department.can.delete"
                                variant="destructive"
                                size="sm"
                                type="button"
                                @click="deleteDepartment(department.id)"
                            >
                                Delete
                            </Button>
                        </div>
                    </td>
                </tr>
                <tr v-if="departments.length === 0">
                    <td colspan="6" class="py-10 text-center text-muted-foreground">
                        No departments yet.
                    </td>
                </tr>
            </tbody>
        </AdminTable>
    </div>
</template>
