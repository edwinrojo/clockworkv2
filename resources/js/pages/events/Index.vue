<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import EventStatusBadge from '@/components/admin/EventStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { attendances, create, destroy, edit, index, live } from '@/routes/events';
import type { EventRow } from '@/types';

defineProps<{
    events: EventRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Events', href: index() }],
    },
});

const page = usePage();
const canCreate = computed(() => page.props.auth.can.events.create);

function formatSchedule(startsAt: string, endsAt: string): string {
    const start = new Date(startsAt);
    const end = new Date(endsAt);

    return `${start.toLocaleString()} – ${end.toLocaleTimeString()}`;
}

function deleteEvent(id: string): void {
    if (!confirm('Delete this event? This cannot be undone.')) {
        return;
    }

    router.delete(destroy.url(id));
}
</script>

<template>
    <Head title="Events" />

    <div class="flex flex-col gap-6 p-4">
        <AdminPageHeader
            title="Events"
            description="Schedule convocations and manage attendance settings"
            :create-href="canCreate ? create() : undefined"
            create-label="Add event"
        />

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Event</th>
                        <th class="px-4 py-3 font-medium">Venue</th>
                        <th class="px-4 py-3 font-medium">Schedule</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Attendance</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="event in events"
                        :key="event.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ event.title }}</div>
                            <div class="text-muted-foreground">
                                {{ event.type_label }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ event.venue_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{
                                formatSchedule(event.starts_at, event.ends_at)
                            }}
                        </td>
                        <td class="px-4 py-3">
                            <EventStatusBadge
                                :status="event.status"
                                :label="event.status_label"
                            />
                        </td>
                        <td class="px-4 py-3">
                            {{ event.attendances_count }}
                            <span class="text-muted-foreground">
                                ({{ event.sessions_count }} sessions)
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div
                                class="flex items-center justify-end gap-2"
                            >
                                <Button
                                    v-if="event.can.manageSession || event.can.viewAttendances"
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
                    <tr v-if="events.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No events scheduled yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
