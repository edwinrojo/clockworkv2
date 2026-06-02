<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DepartmentController from '@/actions/App/Http/Controllers/DepartmentController';
import DepartmentForm from '@/components/departments/DepartmentForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
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

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Add department"
                description="Create a new office or division"
            />
            <Button variant="outline" as-child>
                <Link :href="index()">Back</Link>
            </Button>
        </div>

        <DepartmentForm
            :form="DepartmentController.store.form()"
            :parents="parents"
            submit-label="Create department"
        />
    </div>
</template>
