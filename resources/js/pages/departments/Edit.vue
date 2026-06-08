<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DepartmentController from '@/actions/App/Http/Controllers/DepartmentController';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import DepartmentForm from '@/components/departments/DepartmentForm.vue';
import { index } from '@/routes/departments';
import type { DepartmentOption, DepartmentRow } from '@/types';

defineProps<{
    department: DepartmentRow;
    parents: DepartmentOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Departments', href: index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${department.name}`" />

    <div class="admin-page">
        <AdminPageHeader
            :title="department.name"
            description="Update department details"
        />

        <DepartmentForm
            :form="DepartmentController.update.form(department.id)"
            :parents="parents"
            :department="department"
            submit-label="Save changes"
        />
    </div>
</template>
