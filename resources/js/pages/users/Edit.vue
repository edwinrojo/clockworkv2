<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import UserForm from '@/components/users/UserForm.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/users';
import type { UserEditPageProps } from '@/types/admin';

const { managedUser, departments, roles } = defineProps<UserEditPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Users', href: index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${managedUser.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                :title="managedUser.name"
                description="Update account details and access"
            />
            <Button variant="outline" as-child>
                <Link :href="index()">Back</Link>
            </Button>
        </div>

        <UserForm
            :form="UserController.update.form(managedUser.id)"
            :departments="departments"
            :roles="roles"
            :managed-user="managedUser"
            submit-label="Save changes"
        />
    </div>
</template>
