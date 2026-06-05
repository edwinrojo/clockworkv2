<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UserImportController from '@/actions/App/Http/Controllers/UserImportController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/users';
import { template } from '@/routes/users/import';

type DepartmentOption = {
    id: string;
    name: string;
};

type ImportFailure = {
    row: number;
    messages: string[];
};

type ImportPreviewRow = {
    row: number;
    action: string;
    email: string;
    employee_number: string;
};

type ImportResult = {
    created: number;
    updated: number;
    failed: ImportFailure[];
    preview?: ImportPreviewRow[];
};

defineProps<{
    departments: DepartmentOption[];
    requiredColumns: string[];
    optionalColumns: string[];
    importResult?: ImportResult | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Users', href: index() },
            { title: 'Import', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Import employees" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Import employees"
                description="Upload a department CSV to create employee accounts for mobile check-in"
            />
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <a :href="template.url()">Download template</a>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="index()">Back</Link>
                </Button>
            </div>
        </div>

        <div
            class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
        >
            <p class="text-sm font-medium">CSV format (one department per file)</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Required columns:
                <span class="font-mono">{{ requiredColumns.join(', ') }}</span>
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
                Optional:
                <span class="font-mono">{{ optionalColumns.join(', ') }}</span>
            </p>
            <p class="mt-2 text-xs text-muted-foreground">
                Select the department below — all rows are assigned to it. Employee
                numbers are generated automatically (e.g. HR-00001). The
                <span class="font-mono">id_number</span> column sets each employee’s
                initial mobile password. A six-digit email confirmation code is sent
                on import; employees must confirm before signing in. Use preview before
                importing. Maximum 500 rows per file.
            </p>
        </div>

        <div
            v-if="importResult"
            class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
        >
            <p class="text-sm font-medium">Import summary</p>
            <p class="mt-1 text-sm">
                {{ importResult.created }} to create,
                {{ importResult.updated }} to update,
                {{ importResult.failed.length }} failed
            </p>
            <table
                v-if="importResult.preview && importResult.preview.length > 0"
                class="mt-3 w-full text-sm"
            >
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="py-2 font-medium">Row</th>
                        <th class="py-2 font-medium">Action</th>
                        <th class="py-2 font-medium">Employee</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in importResult.preview"
                        :key="row.row"
                        class="border-b last:border-0"
                    >
                        <td class="py-2">{{ row.row }}</td>
                        <td class="py-2 capitalize">{{ row.action }}</td>
                        <td class="py-2">
                            {{ row.employee_number }} · {{ row.email }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <ul
                v-if="importResult.failed.length > 0"
                class="mt-3 max-h-64 space-y-2 overflow-y-auto text-sm"
            >
                <li
                    v-for="failure in importResult.failed"
                    :key="failure.row"
                    class="rounded-md bg-muted/50 px-3 py-2"
                >
                    <span class="font-medium">Row {{ failure.row }}</span>
                    <ul class="mt-1 list-inside list-disc text-muted-foreground">
                        <li
                            v-for="(message, idx) in failure.messages"
                            :key="idx"
                        >
                            {{ message }}
                        </li>
                    </ul>
                </li>
            </ul>
        </div>

        <Form
            :action="UserImportController.store.url()"
            method="post"
            enctype="multipart/form-data"
            class="max-w-lg space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <label for="department_id" class="text-sm font-medium"
                    >Department</label
                >
                <select
                    id="department_id"
                    name="department_id"
                    required
                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                >
                    <option value="" disabled selected>
                        Select department…
                    </option>
                    <option
                        v-for="department in departments"
                        :key="department.id"
                        :value="department.id"
                    >
                        {{ department.name }}
                    </option>
                </select>
                <p
                    v-if="errors.department_id"
                    class="text-sm text-destructive"
                >
                    {{ errors.department_id }}
                </p>
            </div>

            <div class="grid gap-2">
                <label for="file" class="text-sm font-medium">CSV file</label>
                <input
                    id="file"
                    name="file"
                    type="file"
                    accept=".csv,text/csv"
                    required
                    class="text-sm file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground"
                />
                <p v-if="errors.file" class="text-sm text-destructive">
                    {{ errors.file }}
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="update_existing" value="1" />
                Update existing employees in this department (match by email)
            </label>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="dry_run" value="1" />
                Preview only (dry run — no changes saved)
            </label>

            <Button type="submit" :disabled="processing">
                Run import
            </Button>
        </Form>
    </div>
</template>
