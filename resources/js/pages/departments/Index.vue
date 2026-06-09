<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import AdminTableFilters from '@/components/admin/AdminTableFilters.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { create, destroy, edit, index } from '@/routes/departments';
import { confirm } from '@/lib/confirm';
import type { DepartmentRow, Paginated, TableFilters } from '@/types';

defineProps<{
    departments: Paginated<DepartmentRow>;
    filters: TableFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Departments', href: index() }],
    },
});

const page = usePage();
const canCreate = computed(() => page.props.auth.can.departments.create);

async function deleteDepartment(id: string): Promise<void> {
    const confirmed = await confirm({
        title: 'Delete this department?',
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
    <Head title="Departments" />

    <div class="admin-page">
        <AdminPageHeader
            title="Departments"
            description="Manage provincial offices and divisions"
            :create-href="canCreate ? create() : undefined"
            create-label="Add department"
        />

        <AdminTableFilters
            :action="index()"
            :filters="filters"
            search-placeholder="Name or code"
        >
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
                    <th>Code</th>
                    <th>Parent</th>
                    <th>Employees</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="department in departments.data"
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
                <tr v-if="departments.data.length === 0">
                    <td
                        colspan="6"
                        class="py-10 text-center text-muted-foreground"
                    >
                        No departments yet.
                    </td>
                </tr>
            </tbody>
            <template #footer>
                <AdminPagination :paginator="departments" />
            </template>
        </AdminTable>
    </div>
</template>
