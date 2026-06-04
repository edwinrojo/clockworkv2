<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import EventDisplayPinController from '@/actions/App/Http/Controllers/EventDisplayPinController';
import EventSessionController from '@/actions/App/Http/Controllers/EventSessionController';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import EventStatusBadge from '@/components/admin/EventStatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { attendances, edit, index, live } from '@/routes/events';
import { edit as rosterEdit } from '@/routes/events/roster';
import type { EventLiveAttendance, EventLiveSession } from '@/types/admin';

type LiveEvent = {
    id: string;
    title: string;
    status: string;
    status_label: string;
    venue_name: string | null;
    qr_rotation_seconds: number;
    attendances_count: number;
    display_url: string;
    has_display_pin: boolean;
};

type RosterStats = {
    expected: number;
    checked_in: number;
    missing: number;
    present: number;
    late: number;
    manual_override: number;
    roster_scope_label: string;
};

type MissingEmployee = {
    id: string;
    name: string;
    employee_number: string | null;
    department_name: string | null;
};

type DepartmentOption = {
    id: string;
    name: string;
};

type QrPreview = {
    expires_at: number;
    seconds_remaining: number;
};

const props = defineProps<{
    event: LiveEvent;
    session: EventLiveSession | null;
    qr: QrPreview | null;
    recentAttendances: EventLiveAttendance[];
    rosterStats: RosterStats;
    missingEmployees: MissingEmployee[];
    departments: DepartmentOption[];
    filters: { department_id: string | null };
    can: {
        manageSession: boolean;
        viewAttendances: boolean;
        manageAttendances: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Events', href: index() },
            { title: 'Live operations' },
        ],
    },
});

const countdown = ref(props.qr?.seconds_remaining ?? 0);
let pollTimer: ReturnType<typeof setInterval> | null = null;
let countdownTimer: ReturnType<typeof setInterval> | null = null;

const sessionActive = computed(() => props.session?.status === 'active');
const sessionPaused = computed(() => props.session?.status === 'paused');
const hasSession = computed(() => props.session !== null);

function formatTime(iso: string): string {
    return new Date(iso).toLocaleString();
}

function reloadLive(): void {
    router.reload({
        only: [
            'session',
            'qr',
            'recentAttendances',
            'event',
            'rosterStats',
            'missingEmployees',
        ],
    });
}

function filterByDepartment(departmentId: string): void {
    router.get(
        live.url(props.event.id, {
            query: {
                department_id: departmentId === '' ? undefined : departmentId,
            },
        }),
        {},
        { preserveState: true, preserveScroll: true },
    );
}

function endSession(): void {
    if (!confirm('End this check-in session?')) {
        return;
    }

    router.post(EventSessionController.end.url(props.event.id));
}

onMounted(() => {
    pollTimer = setInterval(reloadLive, 5000);
    countdown.value = props.qr?.seconds_remaining ?? 0;

    countdownTimer = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value -= 1;
        }
    }, 1000);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
    if (countdownTimer) {
        clearInterval(countdownTimer);
    }
});
</script>

<template>
    <Head :title="`${event.title} — Live`" />

    <div class="flex flex-col gap-6 p-4">
        <AdminPageHeader
            :title="event.title"
            description="Run check-in session and monitor attendance in real time"
        >
            <template #actions>
                <Button v-if="can.viewAttendances" variant="outline" as-child>
                    <Link :href="attendances(event.id)">All attendances</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="rosterEdit(event.id)">Expected roster</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="edit(event.id)">Edit event</Link>
                </Button>
            </template>
        </AdminPageHeader>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">Expected</p>
                <p class="text-2xl font-bold tabular-nums">
                    {{ rosterStats.expected }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ rosterStats.roster_scope_label }}
                </p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">Checked in</p>
                <p class="text-2xl font-bold tabular-nums text-emerald-600">
                    {{ rosterStats.checked_in }}
                </p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">Missing</p>
                <p class="text-2xl font-bold tabular-nums text-amber-600">
                    {{ rosterStats.missing }}
                </p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">Late</p>
                <p class="text-2xl font-bold tabular-nums">
                    {{ rosterStats.late }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div
                class="space-y-4 rounded-xl border border-sidebar-border/70 p-4 lg:col-span-1 dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Session</h2>
                    <EventStatusBadge
                        :status="event.status"
                        :label="event.status_label"
                    />
                </div>

                <template v-if="session">
                    <p class="text-sm text-muted-foreground">
                        {{ session.status_label }} since
                        {{ formatTime(session.started_at) }}
                        <span v-if="session.started_by_name">
                            · by {{ session.started_by_name }}
                        </span>
                    </p>

                    <div
                        v-if="sessionActive && qr"
                        class="rounded-lg bg-muted/50 p-3 text-sm"
                    >
                        <p class="font-medium">QR rotation</p>
                        <p class="text-muted-foreground">
                            Next rotation in
                            <span class="font-mono text-foreground">{{
                                countdown
                            }}</span>
                            s (every {{ event.qr_rotation_seconds }}s)
                        </p>
                    </div>
                </template>
                <p v-else class="text-sm text-muted-foreground">
                    No check-in session is running.
                </p>

                <div v-if="can.manageSession" class="flex flex-wrap gap-2">
                    <Form
                        v-if="!hasSession"
                        :action="EventSessionController.start.url(event.id)"
                        method="post"
                        class="contents"
                    >
                        <Button type="submit" size="sm">Start session</Button>
                    </Form>

                    <Form
                        v-if="sessionActive"
                        :action="EventSessionController.pause.url(event.id)"
                        method="post"
                        class="contents"
                    >
                        <Button type="submit" size="sm" variant="outline">
                            Pause
                        </Button>
                    </Form>

                    <Form
                        v-if="sessionPaused"
                        :action="EventSessionController.resume.url(event.id)"
                        method="post"
                        class="contents"
                    >
                        <Button type="submit" size="sm" variant="outline">
                            Resume
                        </Button>
                    </Form>

                    <Form
                        v-if="sessionActive"
                        :action="EventSessionController.rotate.url(event.id)"
                        method="post"
                        class="contents"
                    >
                        <Button type="submit" size="sm" variant="secondary">
                            Rotate QR now
                        </Button>
                    </Form>

                    <Button
                        v-if="hasSession"
                        type="button"
                        size="sm"
                        variant="destructive"
                        @click="endSession"
                    >
                        End session
                    </Button>
                </div>

                <div class="border-t pt-4">
                    <p class="text-sm font-medium">Venue display</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Open this URL on a projector or kiosk at the venue:
                    </p>
                    <a
                        :href="event.display_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-2 block break-all text-sm text-primary underline"
                    >
                        {{ event.display_url }}
                    </a>
                    <p
                        v-if="event.has_display_pin"
                        class="mt-2 text-xs text-muted-foreground"
                    >
                        PIN protection is enabled.
                    </p>
                </div>

                <Form
                    v-if="can.manageSession"
                    :action="EventDisplayPinController.update.url(event.id)"
                    method="post"
                    class="space-y-3 border-t pt-4"
                    v-slot="{ errors, processing }"
                >
                    <p class="text-sm font-medium">Display PIN</p>
                    <p class="text-xs text-muted-foreground">
                        Optional 4-digit PIN for the venue display URL. Leave
                        blank to remove.
                    </p>
                    <Input
                        name="pin"
                        type="password"
                        inputmode="numeric"
                        maxlength="4"
                        placeholder="••••"
                        autocomplete="off"
                    />
                    <InputError :message="errors.pin" />
                    <Button type="submit" size="sm" variant="outline" :disabled="processing">
                        Save PIN
                    </Button>
                </Form>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 p-4 lg:col-span-2 dark:border-sidebar-border"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold">Recent check-ins</h2>
                    <span class="text-2xl font-bold tabular-nums">{{
                        event.attendances_count
                    }}</span>
                </div>

                <table class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="pb-2 font-medium">Employee</th>
                            <th class="pb-2 font-medium">Time</th>
                            <th class="pb-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in recentAttendances"
                            :key="row.id"
                            class="border-b last:border-0"
                        >
                            <td class="py-2">
                                <div>{{ row.employee_name }}</div>
                                <div
                                    v-if="row.employee_number"
                                    class="text-muted-foreground"
                                >
                                    {{ row.employee_number }}
                                </div>
                            </td>
                            <td class="py-2 text-muted-foreground">
                                {{ formatTime(row.checked_in_at) }}
                            </td>
                            <td class="py-2 text-muted-foreground">
                                {{ row.status_label ?? row.status }}
                            </td>
                        </tr>
                        <tr v-if="recentAttendances.length === 0">
                            <td
                                colspan="3"
                                class="py-6 text-center text-muted-foreground"
                            >
                                No check-ins yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <div
                class="flex flex-col gap-4 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <h2 class="font-semibold">
                    Missing employees ({{ missingEmployees.length }})
                </h2>
                <div class="flex items-center gap-2">
                    <Label for="department_filter" class="sr-only"
                        >Department</Label
                    >
                    <select
                        id="department_filter"
                        class="flex h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                        :value="filters.department_id ?? ''"
                        @change="
                            filterByDepartment(
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option value="">All departments</option>
                        <option
                            v-for="dept in departments"
                            :key="dept.id"
                            :value="dept.id"
                        >
                            {{ dept.name }}
                        </option>
                    </select>
                </div>
            </div>
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/30 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Employee</th>
                        <th class="px-4 py-3 font-medium">Department</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="employee in missingEmployees"
                        :key="employee.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ employee.name }}</div>
                            <div class="text-muted-foreground">
                                {{ employee.employee_number ?? '—' }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ employee.department_name ?? '—' }}
                        </td>
                    </tr>
                    <tr v-if="missingEmployees.length === 0">
                        <td
                            colspan="2"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Everyone expected has checked in.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
