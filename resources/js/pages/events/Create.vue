<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import EventController from '@/actions/App/Http/Controllers/EventController';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import EventForm from '@/components/events/EventForm.vue';
import { index } from '@/routes/events';
import type { EventFormOptions } from '@/types/admin';

const { venues, types, statuses, duplicatePolicies } =
    defineProps<EventFormOptions>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Events', href: index() },
            { title: 'Create', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Add event" />

    <div class="admin-page">
        <AdminPageHeader
            title="Add event"
            description="Schedule a new attendance event"
        />

        <EventForm
            :form="EventController.store.form()"
            :venues="venues"
            :types="types"
            :statuses="statuses"
            :duplicate-policies="duplicatePolicies"
            submit-label="Create event"
        />
    </div>
</template>
