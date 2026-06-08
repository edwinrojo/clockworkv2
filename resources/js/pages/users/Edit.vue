<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import DeviceChangeRequestController from '@/actions/App/Http/Controllers/DeviceChangeRequestController';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import UserForm from '@/components/users/UserForm.vue';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/users';
import type { UserEditPageProps } from '@/types/admin';

const { managedUser, departments, roles } = defineProps<UserEditPageProps>();

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

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                :title="managedUser.name"
                description="Update account details and access"
            />
            <Button variant="outline" as-child>
                <Link :href="index()">Back</Link>
            </Button>
        </div>

        <UserForm
            :form="UserController.update.form(managedUser.id)"
            :departments="departments"
            :roles="roles"
            :managed-user="managedUser"
            submit-label="Save changes"
        />

        <div
            v-if="managedUser.can.managePassword && !managedUser.email_verified_at"
            class="admin-card max-w-xl space-y-4 p-4"
        >
            <p class="text-sm font-medium">Email verification</p>
            <p class="text-sm text-muted-foreground">
                Send a six-digit confirmation code to the employee’s email.
                They must enter it in the mobile app before they can sign in.
            </p>
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
        </div>

        <div
            v-if="managedUser.can.managePassword"
            class="admin-card max-w-xl space-y-4 p-4"
        >
            <p class="text-sm font-medium">Employee password</p>
            <p class="text-sm text-muted-foreground">
                Send a mobile reset email or set a temporary password. Both
                revoke active Flutter sessions.
            </p>
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
        </div>

        <div
            v-if="
                managedUser.can.revokeTokens ||
                managedUser.registered_device ||
                managedUser.pending_device_change
            "
            class="admin-card max-w-xl space-y-4 p-4"
        >
            <div>
                <p class="text-sm font-medium">Registered mobile device</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Each employee may sign in on one approved device. A new
                    phone requires administrator approval.
                </p>
            </div>

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
                <Form
                    v-if="managedUser.can.unlinkDevice"
                    :action="UserController.unlinkDevice.url(managedUser.id)"
                    method="post"
                    v-slot="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="outline"
                        size="sm"
                        :disabled="processing"
                    >
                        Unlink device
                    </Button>
                </Form>
            </div>
        </div>

        <div
            v-if="managedUser.can.revokeTokens"
            class="admin-card max-w-xl p-4"
        >
            <p class="text-sm font-medium">Mobile sessions</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Revoke all Sanctum tokens on the employee’s assigned device.
                They must sign in again in the Flutter app.
            </p>
            <Form
                :action="UserController.revokeTokens.url(managedUser.id)"
                method="post"
                class="mt-4"
                v-slot="{ processing }"
            >
                <Button
                    type="submit"
                    variant="outline"
                    size="sm"
                    :disabled="processing"
                >
                    Revoke mobile sessions
                </Button>
            </Form>
        </div>
    </div>
</template>
