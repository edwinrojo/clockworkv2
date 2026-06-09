<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import DeviceChangeRequestController from '@/actions/App/Http/Controllers/DeviceChangeRequestController';
import UserController from '@/actions/App/Http/Controllers/UserController';
import AdminFormSection from '@/components/admin/AdminFormSection.vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import UserForm from '@/components/users/UserForm.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/users';
import { confirm } from '@/lib/confirm';
import type { UserEditPageProps } from '@/types/admin';

const { managedUser, departments, roles } = defineProps<UserEditPageProps>();

async function unlinkDevice(): Promise<void> {
    const confirmed = await confirm({
        title: 'Unlink registered device?',
        description:
            'The employee will need to register their phone again before they can check in on mobile.',
        confirmLabel: 'Unlink device',
        variant: 'warning',
    });

    if (!confirmed) {
        return;
    }

    router.post(UserController.unlinkDevice.url(managedUser.id));
}

async function revokeMobileSessions(): Promise<void> {
    const confirmed = await confirm({
        title: 'Revoke mobile sessions?',
        description:
            'This signs the employee out of the Flutter app on all devices. They must sign in again.',
        confirmLabel: 'Revoke sessions',
        variant: 'warning',
    });

    if (!confirmed) {
        return;
    }

    router.post(UserController.revokeTokens.url(managedUser.id));
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Users', href: index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${managedUser.name}`" />

    <div class="admin-page">
        <AdminPageHeader
            :title="managedUser.name"
            description="Update account details and access"
        />

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <UserForm
                    :form="UserController.update.form(managedUser.id)"
                    :departments="departments"
                    :roles="roles"
                    :managed-user="managedUser"
                    submit-label="Save changes"
                />
            </div>

            <aside class="space-y-6">
        <AdminFormSection
            v-if="managedUser.can.managePassword && !managedUser.email_verified_at"
            title="Email verification"
            description="Send a six-digit confirmation code to the employee’s email. They must enter it in the mobile app before they can sign in."
        >
            <Form
                :action="
                    UserController.sendEmailVerification.url(managedUser.id)
                "
                method="post"
                v-slot="{ processing: sendingVerification }"
            >
                <Button
                    type="submit"
                    variant="outline"
                    size="sm"
                    :disabled="sendingVerification"
                >
                    Send verification code
                </Button>
            </Form>
        </AdminFormSection>

        <AdminFormSection
            v-if="managedUser.can.managePassword"
            title="Employee password"
            description="Send a mobile reset email or set a temporary password. Both revoke active Flutter sessions."
        >
            <Form
                :action="UserController.sendPasswordReset.url(managedUser.id)"
                method="post"
                v-slot="{ processing: sendingReset }"
            >
                <Button
                    type="submit"
                    variant="outline"
                    size="sm"
                    :disabled="sendingReset"
                >
                    Send password reset email
                </Button>
            </Form>
            <Form
                :action="UserController.setPassword.url(managedUser.id)"
                method="post"
                class="space-y-3 border-t pt-4"
                v-slot="{ errors, processing: settingPassword }"
            >
                <div class="grid gap-2">
                    <label for="password" class="text-sm font-medium">
                        Set temporary password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                    />
                    <input
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        placeholder="Confirm password"
                    />
                    <p v-if="errors.password" class="text-sm text-destructive">
                        {{ errors.password }}
                    </p>
                </div>
                <Button
                    type="submit"
                    variant="secondary"
                    size="sm"
                    :disabled="settingPassword"
                >
                    Set password
                </Button>
            </Form>
        </AdminFormSection>

        <AdminFormSection
            v-if="
                managedUser.can.revokeTokens ||
                managedUser.registered_device ||
                managedUser.pending_device_change
            "
            title="Registered mobile device"
            description="Each employee may sign in on one approved device. A new phone requires administrator approval."
        >
            <div
                v-if="managedUser.registered_device"
                class="rounded-lg bg-muted/40 p-4 text-sm"
            >
                <p class="font-medium">
                    {{
                        [
                            managedUser.registered_device.device_name,
                            managedUser.registered_device.device_model,
                        ]
                            .filter(Boolean)
                            .join(' · ') || 'Registered device'
                    }}
                </p>
                <p
                    v-if="managedUser.registered_device.platform"
                    class="mt-1 text-muted-foreground"
                >
                    {{ managedUser.registered_device.platform }}
                    <span v-if="managedUser.registered_device.os_version">
                        · {{ managedUser.registered_device.os_version }}
                    </span>
                </p>
                <p
                    v-if="managedUser.registered_device.last_seen_at"
                    class="mt-2 text-xs text-muted-foreground"
                >
                    Last seen
                    {{
                        new Date(
                            managedUser.registered_device.last_seen_at,
                        ).toLocaleString()
                    }}
                </p>
            </div>

            <div
                v-else
                class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
            >
                No device registered yet. The first successful mobile login
                will auto-register the employee’s phone.
            </div>

            <div
                v-if="managedUser.pending_device_change"
                class="space-y-3 rounded-lg border border-primary/15 bg-primary/5 p-4"
            >
                <p class="text-sm font-medium text-primary">
                    Pending device change
                </p>
                <p class="text-sm">
                    {{
                        [
                            managedUser.pending_device_change.device_name,
                            managedUser.pending_device_change.device_model,
                            managedUser.pending_device_change.platform,
                        ]
                            .filter(Boolean)
                            .join(' · ')
                    }}
                </p>
                <p
                    v-if="managedUser.pending_device_change.reason"
                    class="text-sm text-muted-foreground"
                >
                    “{{ managedUser.pending_device_change.reason }}”
                </p>

                <div
                    v-if="managedUser.can.reviewDeviceChange"
                    class="flex flex-wrap items-end gap-3 border-t border-primary/10 pt-3"
                >
                    <Form
                        :action="
                            DeviceChangeRequestController.approve.url(
                                managedUser.pending_device_change.id,
                            )
                        "
                        method="post"
                        v-slot="{ processing }"
                    >
                        <Button type="submit" size="sm" :disabled="processing">
                            Approve change
                        </Button>
                    </Form>
                    <Form
                        :action="
                            DeviceChangeRequestController.reject.url(
                                managedUser.pending_device_change.id,
                            )
                        "
                        method="post"
                        class="flex min-w-[14rem] flex-1 flex-col gap-2"
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
                            size="sm"
                            :disabled="processing"
                        >
                            Reject
                        </Button>
                    </Form>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <Button
                    v-if="managedUser.can.unlinkDevice"
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="unlinkDevice"
                >
                    Unlink device
                </Button>
            </div>
        </AdminFormSection>

        <AdminFormSection
            v-if="managedUser.can.revokeTokens"
            title="Mobile sessions"
            description="Revoke all Sanctum tokens on the employee’s assigned device. They must sign in again in the Flutter app."
        >
            <Button
                type="button"
                variant="outline"
                size="sm"
                @click="revokeMobileSessions"
            >
                Revoke mobile sessions
            </Button>
        </AdminFormSection>
            </aside>
        </div>
    </div>
</template>
