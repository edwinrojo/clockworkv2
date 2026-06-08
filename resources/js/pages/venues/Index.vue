<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { create, destroy, edit, index } from '@/routes/venues';
import type { VenueRow } from '@/types';

defineProps<{
    venues: VenueRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Venues', href: index() }],
    },
});

const page = usePage();
const canCreate = computed(() => page.props.auth.can.venues.create);

function deleteVenue(id: string): void {
    if (!confirm('Delete this venue? This cannot be undone.')) {
        return;
    }

    router.delete(destroy.url(id));
}
</script>

<template>
    <Head title="Venues" />

    <div class="admin-page">
        <AdminPageHeader
            title="Venues"
            description="Manage event locations and geofences"
            :create-href="canCreate ? create() : undefined"
            create-label="Add venue"
        />

        <AdminTable>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Coordinates</th>
                    <th>Radius (m)</th>
                    <th>Events</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="venue in venues" :key="venue.id">
                    <td>
                        <div class="font-medium">{{ venue.name }}</div>
                        <div v-if="venue.address" class="text-muted-foreground">
                            {{ venue.address }}
                        </div>
                    </td>
                    <td class="text-muted-foreground">
                        {{ venue.latitude }}, {{ venue.longitude }}
                    </td>
                    <td>{{ venue.geofence_radius_meters ?? '—' }}</td>
                    <td>{{ venue.events_count }}</td>
                    <td>
                        <StatusBadge :active="venue.is_active" />
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-2">
                            <Button
                                v-if="venue.can.update"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="edit(venue.id)">Edit</Link>
                            </Button>
                            <Button
                                v-if="venue.can.delete"
                                variant="destructive"
                                size="sm"
                                type="button"
                                @click="deleteVenue(venue.id)"
                            >
                                Delete
                            </Button>
                        </div>
                    </td>
                </tr>
                <tr v-if="venues.length === 0">
                    <td colspan="6" class="py-10 text-center text-muted-foreground">
                        No venues yet.
                    </td>
                </tr>
            </tbody>
        </AdminTable>
    </div>
</template>
