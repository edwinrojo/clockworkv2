<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { EventRow, SelectOption, VenueOption } from '@/types/admin';

type FormBinding = {
    action: string;
    method: 'get' | 'post' | 'put' | 'patch' | 'delete';
};

type ScheduleRow = {
    event_date: string;
    check_in_time: string;
    check_out_time: string;
    late_cutoff_time: string;
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

const props = defineProps<Props>();

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

function tomorrowDate(): string {
    const date = new Date();
    date.setDate(date.getDate() + 1);

    return date.toISOString().slice(0, 10);
}

function defaultScheduleRow(): ScheduleRow {
    return {
        event_date: tomorrowDate(),
        check_in_time: '08:00',
        check_out_time: '17:00',
        late_cutoff_time: '09:00',
    };
}

const isMultiDay = ref(props.event?.is_multi_day ?? false);

const schedule = ref<ScheduleRow[]>(
    props.event?.schedule?.length
        ? props.event.schedule.map((row) => ({ ...row }))
        : [defaultScheduleRow()],
);

watch(isMultiDay, (multiDay, wasMultiDay) => {
    if (multiDay === wasMultiDay) {
        return;
    }

    if (multiDay && schedule.value.length === 1) {
        schedule.value = [...schedule.value, defaultScheduleRow()];
    }

    if (!multiDay && schedule.value.length > 1) {
        schedule.value = [schedule.value[0]];
    }
});

function addDate(): void {
    schedule.value.push(defaultScheduleRow());
}

function removeDate(index: number): void {
    if (schedule.value.length > 1) {
        schedule.value.splice(index, 1);
    }
}
</script>

<template>
    <Form v-bind="form" class="max-w-3xl space-y-6" v-slot="{ errors, processing }">
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

        <div class="admin-card space-y-4 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-medium">Event dates</h3>
                    <p class="text-sm text-muted-foreground">
                        Dates only — set check-in, check-out, and on-time cutoff
                        times for each day.
                    </p>
                </div>
                <label
                    for="is_multi_day"
                    class="flex cursor-pointer items-center gap-2 text-sm"
                >
                    <Checkbox id="is_multi_day" v-model="isMultiDay" />
                    <span>Multiple dates</span>
                </label>
            </div>

            <input
                type="hidden"
                name="is_multi_day"
                :value="isMultiDay ? '1' : '0'"
            />

            <InputError :message="errors.schedule" />

            <div
                v-for="(row, index) in schedule"
                :key="index"
                class="admin-card-muted space-y-3 p-4"
            >
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-medium">
                        {{
                            isMultiDay
                                ? `Day ${index + 1}`
                                : 'Event date'
                        }}
                    </p>
                    <Button
                        v-if="isMultiDay && schedule.length > 1"
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeDate(index)"
                    >
                        Remove
                    </Button>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label :for="`schedule_${index}_event_date`">Date</Label>
                        <Input
                            :id="`schedule_${index}_event_date`"
                            :name="`schedule[${index}][event_date]`"
                            type="date"
                            v-model="row.event_date"
                            required
                        />
                        <InputError
                            :message="errors[`schedule.${index}.event_date`]"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`schedule_${index}_check_in_time`"
                            >Check-in opens</Label
                        >
                        <Input
                            :id="`schedule_${index}_check_in_time`"
                            :name="`schedule[${index}][check_in_time]`"
                            type="time"
                            v-model="row.check_in_time"
                            required
                        />
                        <InputError
                            :message="errors[`schedule.${index}.check_in_time`]"
                        />
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label :for="`schedule_${index}_check_out_time`"
                            >Check-out time</Label
                        >
                        <Input
                            :id="`schedule_${index}_check_out_time`"
                            :name="`schedule[${index}][check_out_time]`"
                            type="time"
                            v-model="row.check_out_time"
                            required
                        />
                        <InputError
                            :message="errors[`schedule.${index}.check_out_time`]"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`schedule_${index}_late_cutoff_time`"
                            >On-time cutoff (late after)</Label
                        >
                        <Input
                            :id="`schedule_${index}_late_cutoff_time`"
                            :name="`schedule[${index}][late_cutoff_time]`"
                            type="time"
                            v-model="row.late_cutoff_time"
                            required
                        />
                        <InputError
                            :message="
                                errors[`schedule.${index}.late_cutoff_time`]
                            "
                        />
                    </div>
                </div>
            </div>

            <Button
                v-if="isMultiDay"
                type="button"
                variant="outline"
                size="sm"
                @click="addDate"
            >
                Add date
            </Button>
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
