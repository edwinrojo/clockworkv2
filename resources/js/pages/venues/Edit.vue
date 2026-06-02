<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import VenueController from '@/actions/App/Http/Controllers/VenueController';
import VenueForm from '@/components/venues/VenueForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/venues';
import type { VenueRow } from '@/types';

defineProps<{
    venue: VenueRow;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Venues', href: index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${venue.name}`" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                :title="venue.name"
                description="Update venue location and geofence"
            />
            <Button variant="outline" as-child>
                <Link :href="index()">Back</Link>
            </Button>
        </div>

        <VenueForm
            :form="VenueController.update.form(venue.id)"
            :venue="venue"
            submit-label="Save changes"
        />
    </div>
</template>
