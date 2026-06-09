<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import AdminTableFilters from '@/components/admin/AdminTableFilters.vue';
import EventStatusBadge from '@/components/admin/EventStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { attendances, create, destroy, edit, index, live } from '@/routes/events';
import { confirm } from '@/lib/confirm';
import type {
    EventRow,
    Paginated,
    SelectOption,
    TableFilters,
    VenueOption,
} from '@/types';

defineProps<{
    events: Paginated<EventRow>;
    filters: TableFilters;
    statuses: SelectOption[];
    venues: VenueOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Events', href: index() }],
    },
});

const page = usePage();
const canCreate = computed(() => page.props.auth.can.events.create);

function formatDate(value: string): string {
    return new Date(`${value}T00:00:00`).toLocaleDateString();
}

function formatSchedule(event: EventRow): string {
    if (event.schedule.length === 1) {
        return formatDate(event.schedule[0].event_date);
    }

    const first = event.schedule[0]?.event_date;
    const last = event.schedule[event.schedule.length - 1]?.event_date;

    if (first && last) {
        return `${formatDate(first)} – ${formatDate(last)}`;
    }

    return formatDate(event.starts_at);
}

async function deleteEvent(id: string): Promise<void> {
    const confirmed = await confirm({
        title: 'Delete this event?',
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
    <Head title="Events" />

    <div class="admin-page">
        <AdminPageHeader
            title="Events"
            description="Schedule convocations and manage attendance settings"
            :create-href="canCreate ? create() : undefined"
            create-label="Add event"
        />

        <AdminTableFilters
            :action="index()"
            :filters="filters"
            search-placeholder="Event title"
        >
            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <select
                    id="status"
                    name="status"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                >
                    <option value="">All statuses</option>
                    <option
                        v-for="status in statuses"
                        :key="status.value"
                        :value="status.value"
                        :selected="filters.status === status.value"
                    >
                        {{ status.label }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="venue_id">Venue</Label>
                <select
                    id="venue_id"
                    name="venue_id"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                >
                    <option value="">All venues</option>
                    <option
                        v-for="venue in venues"
                        :key="venue.id"
                        :value="venue.id"
                        :selected="filters.venue_id === venue.id"
                    >
                        {{ venue.name }}
                    </option>
                </select>
            </div>
        </AdminTableFilters>

        <AdminTable>
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Venue</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th>Attendance</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="event in events.data" :key="event.id">
                    <td>
                        <div class="font-medium">{{ event.title }}</div>
                        <div class="text-muted-foreground">
                            {{ event.type_label }}
                        </div>
                    </td>
                    <td class="text-muted-foreground">
                        {{ event.venue_name ?? '—' }}
                    </td>
                    <td class="text-muted-foreground">
                        {{ formatSchedule(event) }}
                    </td>
                    <td>
                        <EventStatusBadge
                            :status="event.status"
                            :label="event.status_label"
                        />
                    </td>
                    <td>
                        {{ event.attendances_count }}
                        <span class="text-muted-foreground">
                            ({{ event.sessions_count }} sessions)
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-2">
                            <Button
                                v-if="
                                    event.can.manageSession ||
                                    event.can.viewAttendances
                                "
                                variant="secondary"
                                size="sm"
                                as-child
                            >
                                <Link :href="live(event.id)">Live</Link>
                            </Button>
                            <Button
                                v-if="event.can.viewAttendances"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="attendances(event.id)"
                                    >Attendances</Link
                                >
                            </Button>
                            <Button
                                v-if="event.can.update"
                                variant="outline"
                                size="sm"
                                as-child
                            >
                                <Link :href="edit(event.id)">Edit</Link>
                            </Button>
                            <Button
                                v-if="event.can.delete"
                                variant="destructive"
                                size="sm"
                                type="button"
                                @click="deleteEvent(event.id)"
                            >
                                Delete
                            </Button>
                        </div>
                    </td>
                </tr>
                <tr v-if="events.data.length === 0">
                    <td
                        colspan="6"
                        class="py-10 text-center text-muted-foreground"
                    >
                        No events scheduled yet.
                    </td>
                </tr>
            </tbody>
            <template #footer>
                <AdminPagination :paginator="events" />
            </template>
        </AdminTable>
    </div>
</template>
