<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DepartmentController from '@/actions/App/Http/Controllers/DepartmentController';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import DepartmentForm from '@/components/departments/DepartmentForm.vue';
import { index } from '@/routes/departments';
import type { DepartmentOption } from '@/types';

defineProps<{
    parents: DepartmentOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Departments', href: index() },
            { title: 'Create', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Add department" />

    <div class="admin-page">
        <AdminPageHeader
            title="Add department"
            description="Create a new office or division"
        />

        <DepartmentForm
            :form="DepartmentController.store.form()"
            :parents="parents"
            submit-label="Create department"
        />
    </div>
</template>
