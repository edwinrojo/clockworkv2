<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/admin';

type Props = {
    paginator: Pick<
        Paginated<unknown>,
        'links' | 'from' | 'to' | 'total' | 'current_page' | 'last_page'
    >;
};

const props = defineProps<Props>();

const summary = computed(() => {
    if (props.paginator.total === 0) {
        return 'No results';
    }

    if (props.paginator.from === null || props.paginator.to === null) {
        return `${props.paginator.total} results`;
    }

    return `Showing ${props.paginator.from}–${props.paginator.to} of ${props.paginator.total}`;
});

const pageLinks = computed(() =>
    props.paginator.links.filter((link) => !['&laquo; Previous', 'Next &raquo;'].includes(link.label)),
);
</script>

<template>
    <div
        v-if="paginator.last_page > 1 || paginator.total > 0"
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-sm text-muted-foreground">{{ summary }}</p>

        <div v-if="paginator.last_page > 1" class="flex flex-wrap gap-1">
            <Button
                v-for="link in paginator.links"
                :key="`${link.label}-${link.active}`"
                variant="outline"
                size="sm"
                :disabled="!link.url"
                as-child
            >
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    v-html="link.label"
                />
                <span v-else v-html="link.label" />
            </Button>
        </div>
    </div>
</template>
