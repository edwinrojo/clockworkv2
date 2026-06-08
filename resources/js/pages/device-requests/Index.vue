<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import DeviceChangeRequestController from '@/actions/App/Http/Controllers/DeviceChangeRequestController';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import { Button } from '@/components/ui/button';
import { edit as editUser } from '@/routes/users';
import { index as deviceRequestsIndex } from '@/routes/device-change-requests';

type DeviceSummary = {
    device_name: string | null;
    device_model: string | null;
    platform: string | null;
    os_version?: string | null;
    last_seen_at?: string | null;
};

type DeviceChangeRequestRow = {
    id: string;
    employee: {
        id: string;
        name: string;
        email: string;
        employee_number: string | null;
    };
    current_device: DeviceSummary | null;
    requested_device: DeviceSummary;
    reason: string | null;
    created_at: string;
    can: {
        review: boolean;
    };
};

defineProps<{
    requests: DeviceChangeRequestRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Device change requests', href: deviceRequestsIndex() },
        ],
    },
});

function formatTime(iso: string): string {
    return new Date(iso).toLocaleString();
}

function deviceLabel(device: DeviceSummary | null): string {
    if (!device) {
        return 'No registered device';
    }

    return [device.device_name, device.device_model, device.platform]
        .filter(Boolean)
        .join(' · ');
}
</script>

<template>
    <Head title="Device change requests" />

    <div class="flex flex-col gap-6 p-4">
        <AdminPageHeader
            title="Device change requests"
            description="Review employee requests to sign in on a new phone"
        />

        <div
            v-if="requests.length === 0"
            class="admin-card p-8 text-center text-sm text-muted-foreground"
        >
            No pending device change requests.
        </div>

        <div v-else class="flex flex-col gap-4">
            <article
                v-for="request in requests"
                :key="request.id"
                class="admin-card space-y-4 p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-medium">
                            {{ request.employee.name }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ request.employee.email }}
                            <span v-if="request.employee.employee_number">
                                · {{ request.employee.employee_number }}
                            </span>
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Requested {{ formatTime(request.created_at) }}
                        </p>
                    </div>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="editUser(request.employee.id)">
                            View employee
                        </Link>
                    </Button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg bg-muted/40 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            Current device
                        </p>
                        <p class="mt-2 text-sm">
                            {{ deviceLabel(request.current_device) }}
                        </p>
                        <p
                            v-if="request.current_device?.last_seen_at"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            Last seen
                            {{ formatTime(request.current_device.last_seen_at) }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-primary/15 bg-primary/5 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-primary">
                            Requested device
                        </p>
                        <p class="mt-2 text-sm">
                            {{ deviceLabel(request.requested_device) }}
                        </p>
                        <p
                            v-if="request.requested_device.os_version"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            {{ request.requested_device.os_version }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="request.reason"
                    class="rounded-lg bg-muted/30 px-4 py-3 text-sm text-muted-foreground"
                >
                    “{{ request.reason }}”
                </p>

                <div
                    v-if="request.can.review"
                    class="flex flex-wrap items-end gap-3 border-t pt-4"
                >
                    <Form
                        :action="
                            DeviceChangeRequestController.approve.url(
                                request.id,
                            )
                        "
                        method="post"
                        v-slot="{ processing }"
                    >
                        <Button type="submit" :disabled="processing">
                            Approve device change
                        </Button>
                    </Form>

                    <Form
                        :action="
                            DeviceChangeRequestController.reject.url(
                                request.id,
                            )
                        "
                        method="post"
                        class="flex min-w-[16rem] flex-1 flex-col gap-2"
                        v-slot="{ processing }"
                    >
                        <textarea
                            name="rejection_reason"
                            rows="2"
                            placeholder="Optional rejection reason"
                            class="flex min-h-[4rem] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="processing"
                        >
                            Reject
                        </Button>
                    </Form>
                </div>
            </article>
        </div>
    </div>
</template>
