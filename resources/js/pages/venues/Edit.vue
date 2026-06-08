<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import VenueController from '@/actions/App/Http/Controllers/VenueController';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import VenueForm from '@/components/venues/VenueForm.vue';
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

    <div class="admin-page">
        <AdminPageHeader
            :title="venue.name"
            description="Update venue location and geofence"
        />

        <VenueForm
            :form="VenueController.update.form(venue.id)"
            :venue="venue"
            submit-label="Save changes"
        />
    </div>
</template>
