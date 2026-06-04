<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { live } from '@/routes/events';
import { index as reportsIndex, show as reportShow } from '@/routes/reports';

type LiveEvent = {
    id: string;
    title: string;
    venue_name: string | null;
    attendances_count: number;
    session_active: boolean;
    starts_at: string;
};

type UpcomingEvent = {
    id: string;
    title: string;
    venue_name: string | null;
    status: string;
    status_label: string;
    starts_at: string;
};

type RecentCheckIn = {
    id: string;
    employee_name: string;
    employee_number: string | null;
    event_id: string;
    event_title: string;
    checked_in_at: string;
    status_label: string;
};

defineProps<{
    live_events_count: number;
    check_ins_today: number;
    scheduled_this_week: number;
    live_events: LiveEvent[];
    upcoming_events: UpcomingEvent[];
    recent_check_ins: RecentCheckIn[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

function formatTime(iso: string): string {
    return new Date(iso).toLocaleString();
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4">
        <AdminPageHeader
            title="Operations dashboard"
            description="Live convocations and check-in activity at a glance"
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">Live events</p>
                <p class="mt-1 text-3xl font-bold tabular-nums">
                    {{ live_events_count }}
                </p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">Check-ins today</p>
                <p class="mt-1 text-3xl font-bold tabular-nums">
                    {{ check_ins_today }}
                </p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">Scheduled (7 days)</p>
                <p class="mt-1 text-3xl font-bold tabular-nums">
                    {{ scheduled_this_week }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold">Live events</h2>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="reportsIndex()">All reports</Link>
                    </Button>
                </div>
                <ul v-if="live_events.length > 0" class="space-y-3">
                    <li
                        v-for="event in live_events"
                        :key="event.id"
                        class="flex items-start justify-between gap-3 rounded-lg bg-muted/40 p-3"
                    >
                        <div>
                            <p class="font-medium">{{ event.title }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ event.venue_name ?? 'No venue' }} ·
                                {{ event.attendances_count }} checked in
                            </p>
                            <p
                                class="mt-1 text-xs"
                                :class="
                                    event.session_active
                                        ? 'text-emerald-600'
                                        : 'text-amber-600'
                                "
                            >
                                {{
                                    event.session_active
                                        ? 'Session active'
                                        : 'No active session'
                                }}
                            </p>
                        </div>
                        <Button size="sm" as-child>
                            <Link :href="live(event.id)">Live ops</Link>
                        </Button>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    No events are live right now.
                </p>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <h2 class="mb-4 font-semibold">Upcoming events</h2>
                <ul v-if="upcoming_events.length > 0" class="space-y-3">
                    <li
                        v-for="event in upcoming_events"
                        :key="event.id"
                        class="rounded-lg bg-muted/40 p-3"
                    >
                        <p class="font-medium">{{ event.title }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ formatTime(event.starts_at) }} ·
                            {{ event.status_label }}
                        </p>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    No upcoming events in the next week.
                </p>
            </div>
        </div>

        <div
            class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
        >
            <h2 class="mb-4 font-semibold">Recent check-ins</h2>
            <table v-if="recent_check_ins.length > 0" class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="pb-2 font-medium">Employee</th>
                        <th class="pb-2 font-medium">Event</th>
                        <th class="pb-2 font-medium">Time</th>
                        <th class="pb-2 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="checkIn in recent_check_ins"
                        :key="checkIn.id"
                        class="border-b last:border-0"
                    >
                        <td class="py-2">
                            {{ checkIn.employee_name }}
                            <span
                                v-if="checkIn.employee_number"
                                class="text-muted-foreground"
                            >
                                ({{ checkIn.employee_number }})
                            </span>
                        </td>
                        <td class="py-2">
                            <Link
                                :href="reportShow(checkIn.event_id)"
                                class="text-primary underline"
                            >
                                {{ checkIn.event_title }}
                            </Link>
                        </td>
                        <td class="py-2 text-muted-foreground">
                            {{ formatTime(checkIn.checked_in_at) }}
                        </td>
                        <td class="py-2">{{ checkIn.status_label }}</td>
                    </tr>
                </tbody>
            </table>
            <p v-else class="text-sm text-muted-foreground">
                No check-ins recorded yet.
            </p>
        </div>
    </div>
</template>
