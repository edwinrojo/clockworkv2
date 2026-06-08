<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import AdminFormSection from '@/components/admin/AdminFormSection.vue';
import InputError from '@/components/InputError.vue';
import VenueGeofenceMap from '@/components/venues/VenueGeofenceMap.vue';
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
    <Form
        v-bind="form"
        class="space-y-6"
        v-slot="{ errors, processing }"
    >
        <AdminFormSection
            title="Venue details"
            description="Name and address shown in event planning."
        >
            <div class="grid gap-4 md:grid-cols-2">
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
            </div>
        </AdminFormSection>

        <AdminFormSection
            title="Geofence"
            description="Draw a radius or polygon on the map for mobile check-in validation."
        >
            <VenueGeofenceMap
                :latitude="venue?.latitude ?? 6.75"
                :longitude="venue?.longitude ?? 125.35"
                :radius-meters="venue?.geofence_radius_meters ?? 150"
                :polygon="venue?.geofence_polygon ?? null"
            />
            <InputError :message="errors.latitude" />
            <InputError :message="errors.longitude" />
            <InputError :message="errors.geofence_radius_meters" />
            <InputError :message="errors.geofence_polygon" />
        </AdminFormSection>

        <AdminFormSection title="GPS settings">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="accuracy_buffer_meters">GPS accuracy buffer (m)</Label>
                    <Input
                        id="accuracy_buffer_meters"
                        name="accuracy_buffer_meters"
                        type="number"
                        min="0"
                        :default-value="venue?.accuracy_buffer_meters ?? 50"
                        required
                    />
                    <p class="text-xs text-muted-foreground">
                        Extra meters added to reported GPS accuracy during
                        check-in.
                    </p>
                    <InputError :message="errors.accuracy_buffer_meters" />
                </div>

                <div class="flex items-end pb-1">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0" />
                        <Checkbox
                            id="is_active"
                            name="is_active"
                            value="1"
                            :default-value="venue?.is_active ?? true"
                        />
                        <Label for="is_active">Active venue</Label>
                    </div>
                </div>
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
