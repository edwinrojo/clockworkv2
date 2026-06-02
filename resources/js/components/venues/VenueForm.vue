<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { VenueRow } from '@/types';

type FormBinding = {
    action: string;
    method: 'get' | 'post' | 'put' | 'patch' | 'delete';
};

type Props = {
    form: FormBinding;
    venue?: VenueRow;
    submitLabel: string;
};

defineProps<Props>();
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
                :default-value="venue?.name"
                required
                placeholder="Venue name"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="address">Address</Label>
            <Input
                id="address"
                name="address"
                :default-value="venue?.address ?? ''"
                placeholder="Street address"
            />
            <InputError :message="errors.address" />
        </div>

        <div class="grid gap-2 sm:grid-cols-2 sm:gap-4">
            <div class="grid gap-2">
                <Label for="latitude">Latitude</Label>
                <Input
                    id="latitude"
                    name="latitude"
                    type="number"
                    step="any"
                    :default-value="venue?.latitude"
                    required
                />
                <InputError :message="errors.latitude" />
            </div>
            <div class="grid gap-2">
                <Label for="longitude">Longitude</Label>
                <Input
                    id="longitude"
                    name="longitude"
                    type="number"
                    step="any"
                    :default-value="venue?.longitude"
                    required
                />
                <InputError :message="errors.longitude" />
            </div>
        </div>

        <div class="grid gap-2 sm:grid-cols-2 sm:gap-4">
            <div class="grid gap-2">
                <Label for="geofence_radius_meters">Geofence radius (m)</Label>
                <Input
                    id="geofence_radius_meters"
                    name="geofence_radius_meters"
                    type="number"
                    min="10"
                    :default-value="venue?.geofence_radius_meters ?? 150"
                    placeholder="150"
                />
                <InputError :message="errors.geofence_radius_meters" />
            </div>
            <div class="grid gap-2">
                <Label for="accuracy_buffer_meters">GPS buffer (m)</Label>
                <Input
                    id="accuracy_buffer_meters"
                    name="accuracy_buffer_meters"
                    type="number"
                    min="0"
                    :default-value="venue?.accuracy_buffer_meters ?? 50"
                    required
                />
                <InputError :message="errors.accuracy_buffer_meters" />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0" />
            <Checkbox
                id="is_active"
                name="is_active"
                value="1"
                :default-value="venue?.is_active ?? true"
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
