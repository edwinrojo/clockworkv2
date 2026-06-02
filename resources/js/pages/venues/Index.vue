<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
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

    <div class="flex flex-col gap-6 p-4">
        <AdminPageHeader
            title="Venues"
            description="Manage event locations and geofences"
            :create-href="canCreate ? create() : undefined"
            create-label="Add venue"
        />

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Coordinates</th>
                        <th class="px-4 py-3 font-medium">Radius (m)</th>
                        <th class="px-4 py-3 font-medium">Events</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="venue in venues"
                        :key="venue.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ venue.name }}</div>
                            <div
                                v-if="venue.address"
                                class="text-muted-foreground"
                            >
                                {{ venue.address }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ venue.latitude }}, {{ venue.longitude }}
                        </td>
                        <td class="px-4 py-3">
                            {{ venue.geofence_radius_meters ?? '—' }}
                        </td>
                        <td class="px-4 py-3">{{ venue.events_count }}</td>
                        <td class="px-4 py-3">
                            <StatusBadge :active="venue.is_active" />
                        </td>
                        <td class="px-4 py-3">
                            <div
                                class="flex items-center justify-end gap-2"
                            >
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
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No venues yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
