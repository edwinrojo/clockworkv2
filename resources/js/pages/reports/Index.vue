<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import EventStatusBadge from '@/components/admin/EventStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { exportMethod, index, show } from '@/routes/reports';

type ReportEventRow = {
    id: string;
    title: string;
    venue_name: string | null;
    status: string;
    status_label: string;
    starts_at: string;
    attendances_count: number;
};

defineProps<{
    filters: { from: string; to: string };
    events: ReportEventRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Reports', href: index() }],
    },
});

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString();
}
</script>

<template>
    <Head title="Reports" />

    <div class="flex flex-col gap-6 p-4">
        <AdminPageHeader
            title="Attendance reports"
            description="Summary of check-ins across events"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <a
                        :href="
                            exportMethod.url({
                                query: { from: filters.from, to: filters.to },
                            })
                        "
                    >
                        Export CSV
                    </a>
                </Button>
            </template>
        </AdminPageHeader>

        <Form
            :action="index.url()"
            method="get"
            class="admin-card flex flex-wrap items-end gap-4 p-4"
        >
            <div class="grid gap-2">
                <Label for="from">From</Label>
                <Input id="from" name="from" type="date" :default-value="filters.from" />
            </div>
            <div class="grid gap-2">
                <Label for="to">To</Label>
                <Input id="to" name="to" type="date" :default-value="filters.to" />
            </div>
            <Button type="submit">Apply</Button>
        </Form>

        <div
            class="admin-panel"
        >
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Event</th>
                        <th class="px-4 py-3 font-medium">Venue</th>
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Attendances</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="event in events"
                        :key="event.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-3 font-medium">{{ event.title }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ event.venue_name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ formatDate(event.starts_at) }}
                        </td>
                        <td class="px-4 py-3">
                            <EventStatusBadge
                                :status="event.status"
                                :label="event.status_label"
                            />
                        </td>
                        <td class="px-4 py-3 tabular-nums">
                            {{ event.attendances_count }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="show(event.id)">View report</Link>
                            </Button>
                        </td>
                    </tr>
                    <tr v-if="events.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No events in this date range.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
