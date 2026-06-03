<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import EventStatusBadge from '@/components/admin/EventStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { attendances, live } from '@/routes/events';
import { index as reportsIndex } from '@/routes/reports';

type ReportTotals = {
    expected_employees: number;
    checked_in: number;
    missing: number;
    present: number;
    late: number;
    manual_override: number;
    attendance_rate: number;
};

type DepartmentBreakdown = {
    department_id: string | null;
    department_name: string;
    total: number;
};

const props = defineProps<{
    event: {
        id: string;
        title: string;
        venue_name: string | null;
        status: string;
        status_label: string;
        starts_at: string;
        attendances_count: number;
    };
    totals: ReportTotals;
    by_department: DepartmentBreakdown[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Reports', href: reportsIndex() },
            { title: 'Event report' },
        ],
    },
});
</script>

<template>
    <Head :title="`${event.title} — Report`" />

    <div class="flex flex-col gap-6 p-4">
        <AdminPageHeader
            :title="event.title"
            :description="event.venue_name ?? 'Attendance report'"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="live(event.id)">Live operations</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="attendances(event.id)">Attendances</Link>
                </Button>
            </template>
        </AdminPageHeader>

        <div class="flex items-center gap-2">
            <EventStatusBadge
                :status="event.status"
                :label="event.status_label"
            />
            <span class="text-sm text-muted-foreground">
                {{ totals.attendance_rate }}% attendance rate
            </span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">Checked in</p>
                <p class="text-3xl font-bold tabular-nums">
                    {{ totals.checked_in }}
                </p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">Missing</p>
                <p class="text-3xl font-bold tabular-nums text-amber-600">
                    {{ totals.missing }}
                </p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">Late</p>
                <p class="text-3xl font-bold tabular-nums">
                    {{ totals.late }}
                </p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-sm text-muted-foreground">Manual overrides</p>
                <p class="text-3xl font-bold tabular-nums">
                    {{ totals.manual_override }}
                </p>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <div class="border-b bg-muted/50 px-4 py-3 font-medium">
                By department
            </div>
            <table class="w-full text-sm">
                <thead class="border-b text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Department</th>
                        <th class="px-4 py-3 font-medium text-right">
                            Check-ins
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in by_department"
                        :key="row.department_id ?? 'none'"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3">{{ row.department_name }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ row.total }}
                        </td>
                    </tr>
                    <tr v-if="by_department.length === 0">
                        <td
                            colspan="2"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No check-ins recorded yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
