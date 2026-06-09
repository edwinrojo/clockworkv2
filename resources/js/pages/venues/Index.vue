<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import AdminTableFilters from '@/components/admin/AdminTableFilters.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { create, destroy, edit, index } from '@/routes/venues';
import { confirm } from '@/lib/confirm';
import type { Paginated, TableFilters, VenueRow } from '@/types';

defineProps<{
    venues: Paginated<VenueRow>;
    filters: TableFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Venues', href: index() }],
    },
});

const page = usePage();
const canCreate = computed(() => page.props.auth.can.venues.create);

async function deleteVenue(id: string): Promise<void> {
    const confirmed = await confirm({
        title: 'Delete this venue?',
        description: 'This cannot be undone.',
        confirmLabel: 'Delete',
        variant: 'destructive',
    });

    if (!confirmed) {
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

        <AdminTableFilters
            :action="index()"
            :filters="filters"
            search-placeholder="Name or address"
        >
            <div class="grid gap-2">
                <Label for="is_active">Status</Label>
                <select
                    id="is_active"
                    name="is_active"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                >
                    <option value="">All</option>
                    <option value="1" :selected="filters.is_active === '1'">
                        Active
                    </option>
                    <option value="0" :selected="filters.is_active === '0'">
                        Inactive
                    </option>
                </select>
            </div>
        </AdminTableFilters>

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
                <tr v-for="venue in venues.data" :key="venue.id">
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
                <tr v-if="venues.data.length === 0">
                    <td
                        colspan="6"
                        class="py-10 text-center text-muted-foreground"
                    >
                        No venues yet.
                    </td>
                </tr>
            </tbody>
            <template #footer>
                <AdminPagination :paginator="venues" />
            </template>
        </AdminTable>
    </div>
</template>
