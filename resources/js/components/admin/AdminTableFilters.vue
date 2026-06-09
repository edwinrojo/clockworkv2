<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    action: string;
    filters: Record<string, string | number | null | undefined>;
    searchPlaceholder?: string;
    showPerPage?: boolean;
};

withDefaults(defineProps<Props>(), {
    searchPlaceholder: 'Search…',
    showPerPage: true,
});

const perPageOptions = [15, 25, 50];
</script>

<template>
    <Form
        :action="action"
        method="get"
        class="admin-card flex flex-wrap items-end gap-4 p-4"
    >
        <div class="grid min-w-[12rem] flex-1 gap-2">
            <Label for="admin-table-search">Search</Label>
            <Input
                id="admin-table-search"
                name="search"
                :default-value="filters.search ?? ''"
                :placeholder="searchPlaceholder"
            />
        </div>

        <slot />

        <div v-if="showPerPage" class="grid gap-2">
            <Label for="per_page">Per page</Label>
            <select
                id="per_page"
                name="per_page"
                class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
            >
                <option
                    v-for="option in perPageOptions"
                    :key="option"
                    :value="option"
                    :selected="Number(filters.per_page ?? 15) === option"
                >
                    {{ option }}
                </option>
            </select>
        </div>

        <div class="flex gap-2">
            <Button type="submit" variant="secondary">Apply</Button>
            <Button type="button" variant="outline" as-child>
                <Link :href="action">Reset</Link>
            </Button>
        </div>
    </Form>
</template>
