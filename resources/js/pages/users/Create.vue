<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/UserController';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import UserForm from '@/components/users/UserForm.vue';
import { index } from '@/routes/users';
import type { UserFormOptions } from '@/types/admin';

const { departments, roles } = defineProps<UserFormOptions>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Users', href: index() },
            { title: 'Create', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Add user" />

    <div class="admin-page">
        <AdminPageHeader
            title="Add user"
            description="Create an employee or admin account"
        />

        <UserForm
            :form="UserController.store.form()"
            :departments="departments"
            :roles="roles"
            submit-label="Create user"
            :require-password="true"
        />
    </div>
</template>
