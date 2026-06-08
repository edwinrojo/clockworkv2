<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import VenueController from '@/actions/App/Http/Controllers/VenueController';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import VenueForm from '@/components/venues/VenueForm.vue';
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

    <div class="admin-page">
        <AdminPageHeader
            title="Add venue"
            description="Define a location and geofence for events"
        />

        <VenueForm
            :form="VenueController.store.form()"
            submit-label="Create venue"
        />
    </div>
</template>
