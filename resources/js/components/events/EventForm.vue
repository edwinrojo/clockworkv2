<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { EventRow, SelectOption, VenueOption } from '@/types/admin';

type FormBinding = {
    action: string;
    method: 'get' | 'post' | 'put' | 'patch' | 'delete';
};

type Props = {
    form: FormBinding;
    venues: VenueOption[];
    types: SelectOption[];
    statuses: SelectOption[];
    duplicatePolicies: SelectOption[];
    event?: EventRow;
    submitLabel: string;
};

defineProps<Props>();

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';
</script>

<template>
    <Form v-bind="form" class="max-w-2xl space-y-6" v-slot="{ errors, processing }">
        <div class="grid gap-2">
            <Label for="title">Title</Label>
            <Input
                id="title"
                name="title"
                :default-value="event?.title"
                required
                placeholder="Monday Convocation"
            />
            <InputError :message="errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <textarea
                id="description"
                name="description"
                rows="3"
                :class="selectClass"
                :default-value="event?.description ?? ''"
                placeholder="Optional details for coordinators"
            />
            <InputError :message="errors.description" />
        </div>

        <div class="grid gap-2 sm:grid-cols-2 sm:gap-4">
            <div class="grid gap-2">
                <Label for="venue_id">Venue</Label>
                <select
                    id="venue_id"
                    name="venue_id"
                    required
                    :class="selectClass"
                >
                    <option value="" disabled :selected="!event?.venue_id">
                        Select a venue
                    </option>
                    <option
                        v-for="venue in venues"
                        :key="venue.id"
                        :value="venue.id"
                        :selected="event?.venue_id === venue.id"
                    >
                        {{ venue.name }}
                    </option>
                </select>
                <InputError :message="errors.venue_id" />
            </div>
            <div class="grid gap-2">
                <Label for="type">Event type</Label>
                <select id="type" name="type" required :class="selectClass">
                    <option
                        v-for="option in types"
                        :key="option.value"
                        :value="option.value"
                        :selected="event?.type === option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="errors.type" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="status">Status</Label>
            <select id="status" name="status" required :class="selectClass">
                <option
                    v-for="option in statuses"
                    :key="option.value"
                    :value="option.value"
                    :selected="event?.status === option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <InputError :message="errors.status" />
        </div>

        <div class="grid gap-2 sm:grid-cols-2 sm:gap-4">
            <div class="grid gap-2">
                <Label for="starts_at">Starts at</Label>
                <Input
                    id="starts_at"
                    name="starts_at"
                    type="datetime-local"
                    :default-value="event?.starts_at"
                    required
                />
                <InputError :message="errors.starts_at" />
            </div>
            <div class="grid gap-2">
                <Label for="ends_at">Ends at</Label>
                <Input
                    id="ends_at"
                    name="ends_at"
                    type="datetime-local"
                    :default-value="event?.ends_at"
                    required
                />
                <InputError :message="errors.ends_at" />
            </div>
        </div>

        <div class="grid gap-2 sm:grid-cols-2 sm:gap-4">
            <div class="grid gap-2">
                <Label for="check_in_opens_at">Check-in opens</Label>
                <Input
                    id="check_in_opens_at"
                    name="check_in_opens_at"
                    type="datetime-local"
                    :default-value="event?.check_in_opens_at ?? ''"
                />
                <InputError :message="errors.check_in_opens_at" />
            </div>
            <div class="grid gap-2">
                <Label for="check_in_closes_at">Check-in closes</Label>
                <Input
                    id="check_in_closes_at"
                    name="check_in_closes_at"
                    type="datetime-local"
                    :default-value="event?.check_in_closes_at ?? ''"
                />
                <InputError :message="errors.check_in_closes_at" />
            </div>
        </div>

        <div class="grid gap-2 sm:grid-cols-2 sm:gap-4">
            <div class="grid gap-2">
                <Label for="qr_rotation_seconds">QR rotation (seconds)</Label>
                <Input
                    id="qr_rotation_seconds"
                    name="qr_rotation_seconds"
                    type="number"
                    min="15"
                    max="300"
                    :default-value="event?.qr_rotation_seconds ?? 60"
                    required
                />
                <InputError :message="errors.qr_rotation_seconds" />
            </div>
            <div class="grid gap-2">
                <Label for="duplicate_policy">Duplicate check-in rule</Label>
                <select
                    id="duplicate_policy"
                    name="duplicate_policy"
                    required
                    :class="selectClass"
                >
                    <option
                        v-for="option in duplicatePolicies"
                        :key="option.value"
                        :value="option.value"
                        :selected="event?.duplicate_policy === option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="errors.duplicate_policy" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">
                {{ submitLabel }}
            </Button>
        </div>
    </Form>
</template>
