<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DepartmentController from '@/actions/App/Http/Controllers/DepartmentController';
import DepartmentForm from '@/components/departments/DepartmentForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
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

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                :title="department.name"
                description="Update department details"
            />
            <Button variant="outline" as-child>
                <Link :href="index()">Back</Link>
            </Button>
        </div>

        <DepartmentForm
            :form="DepartmentController.update.form(department.id)"
            :parents="parents"
            :department="department"
            submit-label="Save changes"
        />
    </div>
</template>
