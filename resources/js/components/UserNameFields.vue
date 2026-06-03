<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type NameValues = {
    first_name?: string;
    middle_name?: string | null;
    last_name?: string;
    suffix?: string | null;
};

type Props = {
    values?: NameValues;
    errors?: Record<string, string | undefined>;
};

withDefaults(defineProps<Props>(), {
    values: () => ({}),
    errors: () => ({}),
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="grid gap-2">
            <Label for="first_name">First name</Label>
            <Input
                id="first_name"
                name="first_name"
                :default-value="values.first_name"
                required
                autocomplete="given-name"
                placeholder="First name"
            />
            <InputError :message="errors.first_name" />
        </div>

        <div class="grid gap-2">
            <Label for="middle_name">Middle name</Label>
            <Input
                id="middle_name"
                name="middle_name"
                :default-value="values.middle_name ?? ''"
                autocomplete="additional-name"
                placeholder="Middle name (optional)"
            />
            <InputError :message="errors.middle_name" />
        </div>

        <div class="grid gap-2">
            <Label for="last_name">Last name</Label>
            <Input
                id="last_name"
                name="last_name"
                :default-value="values.last_name"
                required
                autocomplete="family-name"
                placeholder="Last name"
            />
            <InputError :message="errors.last_name" />
        </div>

        <div class="grid gap-2">
            <Label for="suffix">Suffix</Label>
            <Input
                id="suffix"
                name="suffix"
                :default-value="values.suffix ?? ''"
                autocomplete="honorific-suffix"
                placeholder="Jr., Sr., III (optional)"
            />
            <InputError :message="errors.suffix" />
        </div>
    </div>
</template>
