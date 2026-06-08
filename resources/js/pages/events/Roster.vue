<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import EventRosterController from '@/actions/App/Http/Controllers/EventRosterController';
import AdminFormSection from '@/components/admin/AdminFormSection.vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
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

const showDepartments = computed(() => scope.value === 'departments');
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

    <div class="admin-page">
        <AdminPageHeader
            :title="event.title"
            :description="`Expected attendance roster · ${event.status_label}`"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="live(event.id)">Live ops</Link>
                </Button>
            </template>
        </AdminPageHeader>

        <Form
            :action="EventRosterController.update.url(event.id)"
            method="put"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <AdminFormSection
                title="Roster scope"
                description="Choose who should be counted as expected on the live operations page."
            >
                <div class="grid gap-4 md:grid-cols-2">
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
                    </div>
                    <p class="flex items-end text-sm text-muted-foreground">
                        Missing-employee counts on the live page use this
                        roster.
                    </p>
                </div>
            </AdminFormSection>

            <AdminFormSection
                v-if="showDepartments"
                title="Departments"
                description="Select offices whose employees are expected to attend."
            >
                <div
                    class="grid max-h-80 gap-2 overflow-y-auto rounded-lg bg-muted/30 p-4 sm:grid-cols-2 lg:grid-cols-3"
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
            </AdminFormSection>

            <AdminFormSection
                v-if="showEmployees"
                title="Employees"
                description="Select individual employees expected to attend."
            >
                <div
                    class="grid max-h-80 gap-2 overflow-y-auto rounded-lg bg-muted/30 p-4 sm:grid-cols-2 lg:grid-cols-3"
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
                        <span>
                            {{ employee.name }}
                            <span
                                v-if="employee.employee_number"
                                class="text-muted-foreground"
                            >
                                ({{ employee.employee_number }})
                            </span>
                        </span>
                    </label>
                </div>
                <InputError :message="errors.user_ids" />
            </AdminFormSection>

            <Button type="submit" :disabled="processing">
                Save expected roster
            </Button>
        </Form>
    </div>
</template>
