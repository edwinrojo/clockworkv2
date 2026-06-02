<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EventController from '@/actions/App/Http/Controllers/EventController';
import EventForm from '@/components/events/EventForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
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

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Add event"
                description="Schedule a new attendance event"
            />
            <Button variant="outline" as-child>
                <Link :href="index()">Back</Link>
            </Button>
        </div>

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
