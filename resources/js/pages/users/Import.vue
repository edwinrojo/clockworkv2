<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UserImportController from '@/actions/App/Http/Controllers/UserImportController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/users';
import { template } from '@/routes/users/import';

type ImportFailure = {
    row: number;
    messages: string[];
};

type ImportResult = {
    created: number;
    failed: ImportFailure[];
};

defineProps<{
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
                description="Upload a CSV to create employee accounts for mobile check-in"
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
            <p class="text-sm font-medium">CSV format</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Required columns:
                <span class="font-mono">{{ requiredColumns.join(', ') }}</span>
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
                Optional:
                <span class="font-mono">{{ optionalColumns.join(', ') }}</span>
            </p>
            <p class="mt-2 text-xs text-muted-foreground">
                Department names must match an existing department exactly
                (case-insensitive). Maximum 500 rows per file.
            </p>
        </div>

        <div
            v-if="importResult"
            class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
        >
            <p class="text-sm font-medium">Import summary</p>
            <p class="mt-1 text-sm">
                {{ importResult.created }} created,
                {{ importResult.failed.length }} failed
            </p>
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

            <Button type="submit" :disabled="processing">
                Import employees
            </Button>
        </Form>
    </div>
</template>
