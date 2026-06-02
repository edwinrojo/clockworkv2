<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
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

    <div class="flex flex-col gap-6 p-4">
        <AdminPageHeader
            title="Departments"
            description="Manage provincial offices and divisions"
            :create-href="canCreate ? create() : undefined"
            create-label="Add department"
        />

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Code</th>
                        <th class="px-4 py-3 font-medium">Parent</th>
                        <th class="px-4 py-3 font-medium">Employees</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="department in departments"
                        :key="department.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ department.name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ department.code ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ department.parent_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ department.users_count }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :active="department.is_active" />
                        </td>
                        <td class="px-4 py-3">
                            <div
                                class="flex items-center justify-end gap-2"
                            >
                                <Button
                                    v-if="department.can.update"
                                    variant="outline"
                                    size="sm"
                                    as-child
                                >
                                    <Link :href="edit(department.id)">
                                        Edit
                                    </Link>
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
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No departments yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
