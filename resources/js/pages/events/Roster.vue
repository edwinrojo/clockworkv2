<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EventRosterController from '@/actions/App/Http/Controllers/EventRosterController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { index as eventsIndex, live } from '@/routes/events';

type SelectOption = { value: string; label: string };
type DepartmentOption = { id: string; name: string };
type EmployeeOption = {
    id: string;
    name: string;
    employee_number: string | null;
};

const props = defineProps<{
    event: { id: string; title: string; status_label: string };
    roster: {
        scope: string;
        scope_label: string;
        department_ids: string[];
        user_ids: string[];
    };
    scopes: SelectOption[];
    departments: DepartmentOption[];
    employees: EmployeeOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Events', href: eventsIndex() },
            { title: 'Expected roster', href: '#' },
        ],
    },
});

const scope = ref(props.roster.scope);
const selectedDepartments = ref<string[]>([...props.roster.department_ids]);
const selectedEmployees = ref<string[]>([...props.roster.user_ids]);

const showDepartments = computed(
    () => scope.value === 'departments',
);
const showEmployees = computed(() => scope.value === 'employees');

function toggleDepartment(id: string, checked: boolean): void {
    if (checked) {
        selectedDepartments.value = [...selectedDepartments.value, id];
    } else {
        selectedDepartments.value = selectedDepartments.value.filter(
            (value) => value !== id,
        );
    }
}

function toggleEmployee(id: string, checked: boolean): void {
    if (checked) {
        selectedEmployees.value = [...selectedEmployees.value, id];
    } else {
        selectedEmployees.value = selectedEmployees.value.filter(
            (value) => value !== id,
        );
    }
}

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';
</script>

<template>
    <Head :title="`${event.title} — Expected roster`" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                :title="event.title"
                :description="`Expected attendance roster · ${event.status_label}`"
            />
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="live(event.id)">Live ops</Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="eventsIndex()">Back</Link>
                </Button>
            </div>
        </div>

        <Form
            :action="EventRosterController.update.url(event.id)"
            method="put"
            class="max-w-2xl space-y-6 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="roster_scope">Who is expected to attend?</Label>
                <select
                    id="roster_scope"
                    name="roster_scope"
                    v-model="scope"
                    :class="selectClass"
                >
                    <option
                        v-for="option in scopes"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="errors.roster_scope" />
                <p class="text-xs text-muted-foreground">
                    Missing-employee counts on the live page use this roster.
                </p>
            </div>

            <div v-if="showDepartments" class="space-y-2">
                <Label>Departments</Label>
                <div
                    class="max-h-64 space-y-2 overflow-y-auto rounded-md border p-3"
                >
                    <label
                        v-for="department in departments"
                        :key="department.id"
                        class="flex items-center gap-2 text-sm"
                    >
                        <input
                            type="checkbox"
                            name="department_ids[]"
                            :value="department.id"
                            :checked="
                                selectedDepartments.includes(department.id)
                            "
                            @change="
                                toggleDepartment(
                                    department.id,
                                    ($event.target as HTMLInputElement)
                                        .checked,
                                )
                            "
                        />
                        {{ department.name }}
                    </label>
                </div>
                <InputError :message="errors.department_ids" />
            </div>

            <div v-if="showEmployees" class="space-y-2">
                <Label>Employees</Label>
                <div
                    class="max-h-64 space-y-2 overflow-y-auto rounded-md border p-3"
                >
                    <label
                        v-for="employee in employees"
                        :key="employee.id"
                        class="flex items-center gap-2 text-sm"
                    >
                        <input
                            type="checkbox"
                            name="user_ids[]"
                            :value="employee.id"
                            :checked="selectedEmployees.includes(employee.id)"
                            @change="
                                toggleEmployee(
                                    employee.id,
                                    ($event.target as HTMLInputElement).checked,
                                )
                            "
                        />
                        {{ employee.name }}
                        <span
                            v-if="employee.employee_number"
                            class="text-muted-foreground"
                        >
                            ({{ employee.employee_number }})
                        </span>
                    </label>
                </div>
                <InputError :message="errors.user_ids" />
            </div>

            <Button type="submit" :disabled="processing">
                Save expected roster
            </Button>
        </Form>
    </div>
</template>
