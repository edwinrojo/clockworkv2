<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DepartmentOption, DepartmentRow } from '@/types';

type FormBinding = {
    action: string;
    method: 'get' | 'post' | 'put' | 'patch' | 'delete';
};

type Props = {
    form: FormBinding;
    parents: DepartmentOption[];
    department?: DepartmentRow;
    submitLabel: string;
};

const props = defineProps<Props>();
</script>

<template>
    <Form v-bind="form" class="max-w-xl space-y-6"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                name="name"
                :default-value="department?.name"
                required
                placeholder="Office name"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="code">Code</Label>
            <Input
                id="code"
                name="code"
                :default-value="department?.code ?? ''"
                placeholder="e.g. HRMO"
            />
            <InputError :message="errors.code" />
        </div>

        <div class="grid gap-2">
            <Label for="parent_id">Parent office</Label>
            <select
                id="parent_id"
                name="parent_id"
                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <option value="" :selected="!department?.parent_id">
                    None (top level)
                </option>
                <option
                    v-for="parent in parents"
                    :key="parent.id"
                    :value="parent.id"
                    :selected="department?.parent_id === parent.id"
                >
                    {{ parent.name }}
                </option>
            </select>
            <InputError :message="errors.parent_id" />
        </div>

        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0" />
            <Checkbox
                id="is_active"
                name="is_active"
                value="1"
                :default-value="department?.is_active ?? true"
            />
            <Label for="is_active">Active</Label>
        </div>
        <InputError :message="errors.is_active" />

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">
                {{ submitLabel }}
            </Button>
        </div>
    </Form>
</template>
