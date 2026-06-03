<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import type { InertiaLinkProps } from '@inertiajs/vue3';

type Props = {
    title: string;
    description?: string;
    createHref?: NonNullable<InertiaLinkProps['href']>;
    createLabel?: string;
};

withDefaults(defineProps<Props>(), {
    createLabel: 'Add new',
});
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <Heading :title="title" :description="description" />
        <div class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
            <Button v-if="createHref" as-child>
                <Link :href="createHref">{{ createLabel }}</Link>
            </Button>
        </div>
    </div>
</template>
