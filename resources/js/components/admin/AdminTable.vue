<script setup lang="ts">
import { cn } from '@/lib/utils';

type Props = {
    title?: string;
    description?: string;
    compact?: boolean;
    class?: string;
};

const props = defineProps<Props>();
</script>

<template>
    <div :class="cn('admin-panel', props.class)">
        <div
            v-if="title || $slots.toolbar"
            class="admin-panel-toolbar"
        >
            <div>
                <h2 v-if="title" class="font-semibold">{{ title }}</h2>
                <p
                    v-if="description"
                    class="mt-0.5 text-sm text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <slot name="toolbar" />
        </div>
        <div class="overflow-x-auto">
            <table
                :class="['admin-table', compact && 'admin-table-compact']"
            >
                <slot />
            </table>
        </div>
        <div
            v-if="$slots.footer"
            class="border-t border-border/15 px-5 py-4"
        >
            <slot name="footer" />
        </div>
    </div>
</template>
