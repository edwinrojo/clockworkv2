<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    CalendarClock,
    CalendarDays,
    Users,
} from '@lucide/vue';
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

const statValues: Record<string, number> = {
    live_events_count: props.live_events_count,
    check_ins_today: props.check_ins_today,
    scheduled_this_week: props.scheduled_this_week,
};

function formatTime(iso: string): string {
    return new Date(iso).toLocaleString();
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <AdminPageHeader
            title="Operations dashboard"
            description="Live convocations and check-in activity at a glance"
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <div
                v-for="card in statCards"
                :key="card.key"
                class="admin-card group relative overflow-hidden p-5 transition-shadow hover:shadow-md"
            >
                <div
                    class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary to-primary/40"
                />
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
                        class="admin-card-muted flex items-start justify-between gap-3 p-4 transition-shadow hover:shadow-md"
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

        <div class="admin-panel">
            <div class="bg-muted/20 px-5 py-4">
                <h2 class="font-semibold">Recent check-ins</h2>
            </div>
            <div class="p-5">
                <table
                    v-if="recent_check_ins.length > 0"
                    class="w-full text-sm"
                >
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="pb-3 font-medium">Employee</th>
                            <th class="pb-3 font-medium">Event</th>
                            <th class="pb-3 font-medium">Time</th>
                            <th class="pb-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="checkIn in recent_check_ins"
                            :key="checkIn.id"
                            class="border-t border-border/30 first:border-t-0"
                        >
                            <td class="py-3">
                                {{ checkIn.employee_name }}
                                <span
                                    v-if="checkIn.employee_number"
                                    class="text-muted-foreground"
                                >
                                    ({{ checkIn.employee_number }})
                                </span>
                            </td>
                            <td class="py-3">
                                <Link
                                    :href="reportShow(checkIn.event_id)"
                                    class="font-medium text-primary hover:underline"
                                >
                                    {{ checkIn.event_title }}
                                </Link>
                            </td>
                            <td class="py-3 text-muted-foreground">
                                {{ formatTime(checkIn.checked_in_at) }}
                            </td>
                            <td class="py-3">
                                <span
                                    class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
                                >
                                    {{ checkIn.status_label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-sm text-muted-foreground">
                    No check-ins recorded yet.
                </p>
            </div>
        </div>
    </div>
</template>
