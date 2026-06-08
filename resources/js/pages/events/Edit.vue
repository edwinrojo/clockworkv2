<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EventController from '@/actions/App/Http/Controllers/EventController';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import EventForm from '@/components/events/EventForm.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/events';
import { edit as rosterEdit } from '@/routes/events/roster';
import type { EventEditPageProps } from '@/types/admin';

const { event, venues, types, statuses, duplicatePolicies } =
    defineProps<EventEditPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Events', href: index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${event.title}`" />

    <div class="admin-page">
        <AdminPageHeader
            :title="event.title"
            description="Update event schedule and check-in settings"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="rosterEdit(event.id)">Expected roster</Link>
                </Button>
            </template>
        </AdminPageHeader>

        <EventForm
            :form="EventController.update.form(event.id)"
            :venues="venues"
            :types="types"
            :statuses="statuses"
            :duplicate-policies="duplicatePolicies"
            :event="event"
            submit-label="Save changes"
        />
    </div>
</template>
