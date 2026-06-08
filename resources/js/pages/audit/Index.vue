<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as auditIndex } from '@/routes/audit-log';

type ActivityLogRow = {
    id: string;
    action: string;
    action_label: string;
    user_name: string | null;
    user_email: string | null;
    subject_type: string | null;
    properties: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
};

type PaginatedLogs = {
    data: ActivityLogRow[];
    links: { url: string | null; label: string; active: boolean }[];
};

defineProps<{
    logs: PaginatedLogs;
    filters: { action: string | null; search: string | null };
    actions: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Audit log', href: auditIndex() }],
    },
});

function formatTime(iso: string): string {
    return new Date(iso).toLocaleString();
}
</script>

<template>
    <Head title="Audit log" />

    <div class="admin-page">
        <AdminPageHeader
            title="Audit log"
            description="Track manual overrides, imports, and admin actions"
        />

        <Form
            :action="auditIndex()"
            method="get"
            class="admin-card flex flex-wrap items-end gap-4 p-4"
        >
            <div class="grid gap-2">
                <Label for="search">Search</Label>
                <Input
                    id="search"
                    name="search"
                    :default-value="filters.search ?? ''"
                    placeholder="User or action"
                />
            </div>
            <div class="grid gap-2">
                <Label for="action">Action</Label>
                <select
                    id="action"
                    name="action"
                    class="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                >
                    <option value="">All actions</option>
                    <option
                        v-for="action in actions"
                        :key="action"
                        :value="action"
                        :selected="filters.action === action"
                    >
                        {{ action }}
                    </option>
                </select>
            </div>
            <Button type="submit" variant="secondary">Filter</Button>
        </Form>

        <AdminTable>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="log in logs.data"
                        :key="log.id"
                        class="align-top"
                    >
                        <td class="text-muted-foreground">
                            {{ formatTime(log.created_at) }}
                        </td>
                        <td>
                            <span class="font-medium">{{
                                log.action_label
                            }}</span>
                            <p
                                v-if="log.subject_type"
                                class="text-xs text-muted-foreground"
                            >
                                {{ log.subject_type }}
                            </p>
                        </td>
                        <td>
                            <p>{{ log.user_name ?? 'System' }}</p>
                            <p
                                v-if="log.user_email"
                                class="text-xs text-muted-foreground"
                            >
                                {{ log.user_email }}
                            </p>
                        </td>
                        <td>
                            <pre
                                v-if="log.properties"
                                class="max-w-md overflow-x-auto rounded bg-muted/50 p-2 text-xs"
                                >{{
                                    JSON.stringify(log.properties, null, 2)
                                }}</pre
                            >
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                    </tr>
                    <tr v-if="logs.data.length === 0">
                        <td colspan="4" class="py-10 text-center text-muted-foreground">
                            No audit entries found.
                        </td>
                    </tr>
                </tbody>
        </AdminTable>

        <div v-if="logs.links.length > 3" class="flex flex-wrap gap-2">
            <Button
                v-for="link in logs.links"
                :key="link.label"
                variant="outline"
                size="sm"
                :disabled="!link.url"
                as-child
            >
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    v-html="link.label"
                />
                <span v-else v-html="link.label" />
            </Button>
        </div>
    </div>
</template>
