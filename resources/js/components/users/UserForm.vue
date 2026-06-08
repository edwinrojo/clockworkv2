<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminFormSection from '@/components/admin/AdminFormSection.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import UserNameFields from '@/components/UserNameFields.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SelectOption, UserRow } from '@/types/admin';

type FormBinding = {
    action: string;
    method: 'get' | 'post' | 'put' | 'patch' | 'delete';
};

type Props = {
    form: FormBinding;
    departments: Array<{ id: string; name: string }>;
    roles: SelectOption[];
    managedUser?: UserRow;
    submitLabel: string;
    requirePassword?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    requirePassword: false,
});

const selectedRole = ref(props.managedUser?.role ?? props.roles[0]?.value ?? 'employee');

const isEmployee = computed(() => selectedRole.value === 'employee');

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';
</script>

<template>
    <Form v-bind="form" class="space-y-6" v-slot="{ errors, processing }">
        <AdminFormSection
            title="Personal information"
            description="Legal name and contact details for this account."
        >
            <UserNameFields
                :values="{
                    first_name: managedUser?.first_name,
                    middle_name: managedUser?.middle_name,
                    last_name: managedUser?.last_name,
                    suffix: managedUser?.suffix,
                }"
                :errors="errors"
            />

            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2 md:col-span-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        :default-value="managedUser?.email"
                        required
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>
            </div>
        </AdminFormSection>

        <AdminFormSection
            title="Role & assignment"
            description="Access level and organizational placement for employees."
        >
            <div class="grid gap-4 md:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="role">Role</Label>
                    <select
                        id="role"
                        name="role"
                        required
                        :class="selectClass"
                        v-model="selectedRole"
                    >
                        <option
                            v-for="option in roles"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="errors.role" />
                </div>

                <div v-show="isEmployee" class="grid gap-2">
                    <Label for="employee_number">Employee number</Label>
                    <input
                        v-if="!isEmployee"
                        type="hidden"
                        name="employee_number"
                        value=""
                    />
                    <Input
                        id="employee_number"
                        name="employee_number"
                        :default-value="managedUser?.employee_number ?? ''"
                        :required="isEmployee"
                        placeholder="EMP-00001"
                    />
                    <InputError :message="errors.employee_number" />
                </div>

                <div v-show="isEmployee" class="grid gap-2">
                    <input
                        v-if="!isEmployee"
                        type="hidden"
                        name="department_id"
                        value=""
                    />
                    <Label for="department_id">Department</Label>
                    <select
                        id="department_id"
                        name="department_id"
                        :class="selectClass"
                        :required="isEmployee"
                    >
                        <option value="" :selected="!managedUser?.department_id">
                            Select a department
                        </option>
                        <option
                            v-for="department in departments"
                            :key="department.id"
                            :value="department.id"
                            :selected="managedUser?.department_id === department.id"
                        >
                            {{ department.name }}
                        </option>
                    </select>
                    <InputError :message="errors.department_id" />
                </div>
            </div>
        </AdminFormSection>

        <AdminFormSection
            :title="requirePassword ? 'Password' : 'Change password'"
            :description="
                requirePassword
                    ? 'Set the initial sign-in password for this account.'
                    : 'Leave blank to keep the current password.'
            "
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="password">
                        {{ requirePassword ? 'Password' : 'New password' }}
                    </Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        :required="requirePassword"
                        autocomplete="new-password"
                        :placeholder="
                            requirePassword
                                ? 'Password'
                                : 'Leave blank to keep current'
                        "
                    />
                    <InputError :message="errors.password" />
                </div>
                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        :required="requirePassword"
                        autocomplete="new-password"
                        placeholder="Confirm password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>
            </div>
        </AdminFormSection>

        <AdminFormSection title="Account status">
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0" />
                <Checkbox
                    id="is_active"
                    name="is_active"
                    value="1"
                    :default-value="managedUser?.is_active ?? true"
                />
                <Label for="is_active">Active account</Label>
            </div>
            <InputError :message="errors.is_active" />
        </AdminFormSection>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">
                {{ submitLabel }}
            </Button>
        </div>
    </Form>
</template>
