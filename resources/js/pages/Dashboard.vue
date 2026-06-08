<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    CalendarClock,
    CalendarDays,
    Smartphone,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as deviceRequestsIndex } from '@/routes/device-change-requests';
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

const props = defineProps<{
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

const statCards = [
    {
        key: 'live_events_count',
        label: 'Live events',
        icon: Activity,
        accent: 'from-primary/20 to-primary/5 text-primary',
    },
    {
        key: 'check_ins_today',
        label: 'Check-ins today',
        icon: Users,
        accent: 'from-emerald-500/20 to-emerald-500/5 text-emerald-600 dark:text-emerald-400',
    },
    {
        key: 'scheduled_this_week',
        label: 'Scheduled (7 days)',
        icon: CalendarDays,
        accent: 'from-amber-500/20 to-amber-500/5 text-amber-600 dark:text-amber-400',
    },
] as const;

const page = usePage();

const statValues: Record<string, number> = {
    live_events_count: props.live_events_count,
    check_ins_today: props.check_ins_today,
    scheduled_this_week: props.scheduled_this_week,
};

const pendingDeviceRequests = computed(
    () => page.props.auth.pending_device_change_requests_count ?? 0,
);

function formatTime(iso: string): string {
    return new Date(iso).toLocaleString();
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="admin-page">
        <AdminPageHeader
            title="Operations dashboard"
            description="Live convocations and check-in activity at a glance"
        />

        <Link
            v-if="pendingDeviceRequests > 0"
            :href="deviceRequestsIndex()"
            class="admin-alert"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <Smartphone class="size-5" />
                </div>
                <div>
                    <p class="font-medium">
                        {{ pendingDeviceRequests }} device change request{{
                            pendingDeviceRequests === 1 ? '' : 's'
                        }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Employees are waiting for approval to sign in on a new
                        phone.
                    </p>
                </div>
            </div>
            <Button size="sm" class="shrink-0">Review</Button>
        </Link>

        <div class="grid gap-4 sm:grid-cols-3">
            <div
                v-for="card in statCards"
                :key="card.key"
                class="admin-stat-card"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ card.label }}
                        </p>
                        <p class="mt-2 text-4xl font-bold tabular-nums tracking-tight">
                            {{ statValues[card.key] }}
                        </p>
                    </div>
                    <div
                        :class="[
                            'flex size-11 items-center justify-center rounded-xl bg-gradient-to-br',
                            card.accent,
                        ]"
                    >
                        <component :is="card.icon" class="size-5" />
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="admin-card p-5">
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Activity class="size-5 text-primary" />
                        <h2 class="font-semibold">Live events</h2>
                    </div>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="reportsIndex()">All reports</Link>
                    </Button>
                </div>
                <ul v-if="live_events.length > 0" class="space-y-3">
                    <li
                        v-for="event in live_events"
                        :key="event.id"
                        class="admin-card-muted elevation-hover flex items-start justify-between gap-3 p-4"
                    >
                        <div>
                            <p class="font-medium">{{ event.title }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ event.venue_name ?? 'No venue' }} ·
                                {{ event.attendances_count }} checked in
                            </p>
                            <p
                                class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="
                                    event.session_active
                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                        : 'bg-amber-500/10 text-amber-600 dark:text-amber-400'
                                "
                            >
                                <span
                                    class="size-1.5 rounded-full"
                                    :class="
                                        event.session_active
                                            ? 'bg-emerald-500'
                                            : 'bg-amber-500'
                                    "
                                />
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

            <div class="admin-card p-5">
                <div class="mb-5 flex items-center gap-2">
                    <CalendarClock class="size-5 text-primary" />
                    <h2 class="font-semibold">Upcoming events</h2>
                </div>
                <ul v-if="upcoming_events.length > 0" class="space-y-3">
                    <li
                        v-for="event in upcoming_events"
                        :key="event.id"
                        class="admin-card-muted p-4"
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

        <AdminTable title="Recent check-ins" compact>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Event</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="checkIn in recent_check_ins"
                    :key="checkIn.id"
                >
                    <td>
                        {{ checkIn.employee_name }}
                        <span
                            v-if="checkIn.employee_number"
                            class="text-muted-foreground"
                        >
                            ({{ checkIn.employee_number }})
                        </span>
                    </td>
                    <td>
                        <Link
                            :href="reportShow(checkIn.event_id)"
                            class="font-medium text-primary hover:underline"
                        >
                            {{ checkIn.event_title }}
                        </Link>
                    </td>
                    <td class="text-muted-foreground">
                        {{ formatTime(checkIn.checked_in_at) }}
                    </td>
                    <td>
                        <span
                            class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
                        >
                            {{ checkIn.status_label }}
                        </span>
                    </td>
                </tr>
                <tr v-if="recent_check_ins.length === 0">
                    <td colspan="4" class="py-10 text-center text-muted-foreground">
                        No check-ins recorded yet.
                    </td>
                </tr>
            </tbody>
        </AdminTable>
    </div>
</template>
