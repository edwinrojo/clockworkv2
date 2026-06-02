<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import VenueController from '@/actions/App/Http/Controllers/VenueController';
import VenueForm from '@/components/venues/VenueForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/venues';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Venues', href: index() },
            { title: 'Create', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Add venue" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Add venue"
                description="Define a location and geofence for events"
            />
            <Button variant="outline" as-child>
                <Link :href="index()">Back</Link>
            </Button>
        </div>

        <VenueForm
            :form="VenueController.store.form()"
            submit-label="Create venue"
        />
    </div>
</template>
