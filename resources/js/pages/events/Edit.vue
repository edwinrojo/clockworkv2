<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import EventController from '@/actions/App/Http/Controllers/EventController';
import EventForm from '@/components/events/EventForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/events';
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

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                :title="event.title"
                description="Update event schedule and check-in settings"
            />
            <Button variant="outline" as-child>
                <Link :href="index()">Back</Link>
            </Button>
        </div>

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
