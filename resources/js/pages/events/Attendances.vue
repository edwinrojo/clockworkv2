<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminFormSection from '@/components/admin/AdminFormSection.vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import EventAttendanceController from '@/actions/App/Http/Controllers/EventAttendanceController';
import { live, index } from '@/routes/events';
import type { SelectOption } from '@/types/admin';

type AttendanceRow = {
    id: string;
    employee_name: string;
    employee_number: string | null;
    department_name: string | null;
    checked_in_at: string;
    source: string;
    status: string;
    manual_override_reason: string | null;
    manual_override_by_name: string | null;
};

type EmployeeOption = {
    id: string;
    name: string;
    employee_number: string | null;
};

const props = defineProps<{
    event: {
        id: string;
        title: string;
        venue_name: string | null;
        attendances_count: number;
    };
    attendances: AttendanceRow[];
    employees: EmployeeOption[];
    can: {
        manageAttendances: boolean;
        manageSession: boolean;
    };
}>();

const statusOptions: SelectOption[] = [
    { value: 'present', label: 'Present' },
    { value: 'late', label: 'Late' },
    { value: 'manual_override', label: 'Manual override' },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Events', href: index() },
            { title: 'Attendances' },
        ],
    },
});

const exportUrl = computed(() =>
    EventAttendanceController.exportMethod.url(props.event.id),
);

function formatTime(iso: string): string {
    return new Date(iso).toLocaleString();
}
</script>

<template>
    <Head :title="`${event.title} — Attendances`" />

    <div class="admin-page">
        <AdminPageHeader
            :title="event.title"
            :description="`${event.attendances_count} attendance records · ${event.venue_name ?? 'No venue'}`"
        >
            <template #actions>
                <Button v-if="can.manageSession" variant="outline" as-child>
                    <Link :href="live(event.id)">Live operations</Link>
                </Button>
                <Button variant="outline" as-child>
                    <a :href="exportUrl">Export CSV</a>
                </Button>
            </template>
        </AdminPageHeader>

        <AdminFormSection
            v-if="can.manageAttendances"
            title="Manual check-in"
            description="Record attendance for an employee who could not use the mobile app."
        >
            <Form
                :action="EventAttendanceController.store.url(event.id)"
                method="post"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="user_id">Employee</Label>
                        <select
                            id="user_id"
                            name="user_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="" disabled selected>
                                Select employee
                            </option>
                            <option
                                v-for="employee in employees"
                                :key="employee.id"
                                :value="employee.id"
                            >
                                {{ employee.name }}
                                <template v-if="employee.employee_number">
                                    ({{ employee.employee_number }})
                                </template>
                            </option>
                        </select>
                        <InputError :message="errors.user_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            name="status"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option
                                v-for="option in statusOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError :message="errors.status" />
                    </div>

                    <div class="grid gap-2 md:col-span-1">
                        <Label for="reason">Reason</Label>
                        <Input
                            id="reason"
                            name="reason"
                            required
                            placeholder="Why is this check-in being recorded manually?"
                        />
                        <InputError :message="errors.reason" />
                    </div>
                </div>

                <Button type="submit" :disabled="processing">
                    Record attendance
                </Button>
            </Form>
        </AdminFormSection>

        <AdminTable title="Attendance records">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Checked in</th>
                    <th>Source</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in attendances" :key="row.id">
                    <td>
                        <div class="font-medium">{{ row.employee_name }}</div>
                        <div class="text-muted-foreground">
                            {{ row.employee_number ?? '—' }}
                        </div>
                    </td>
                    <td class="text-muted-foreground">
                        {{ row.department_name ?? '—' }}
                    </td>
                    <td class="text-muted-foreground">
                        {{ formatTime(row.checked_in_at) }}
                    </td>
                    <td class="text-muted-foreground capitalize">
                        {{ row.source }}
                    </td>
                    <td class="text-muted-foreground">
                        <template v-if="row.manual_override_reason">
                            {{ row.manual_override_reason }}
                            <span v-if="row.manual_override_by_name">
                                ({{ row.manual_override_by_name }})
                            </span>
                        </template>
                        <span v-else>—</span>
                    </td>
                </tr>
                <tr v-if="attendances.length === 0">
                    <td colspan="5" class="py-10 text-center text-muted-foreground">
                        No attendance records yet.
                    </td>
                </tr>
            </tbody>
        </AdminTable>
    </div>
</template>
