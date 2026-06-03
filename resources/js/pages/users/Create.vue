<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import UserForm from '@/components/users/UserForm.vue';
import { Button } from '@/components/ui/button';
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

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Add user"
                description="Create an employee or admin account"
            />
            <Button variant="outline" as-child>
                <Link :href="index()">Back</Link>
            </Button>
        </div>

        <UserForm
            :form="UserController.store.form()"
            :departments="departments"
            :roles="roles"
            submit-label="Create user"
            :require-password="true"
        />
    </div>
</template>
