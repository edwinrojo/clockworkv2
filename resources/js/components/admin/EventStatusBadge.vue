<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

const props = defineProps<{
    status: string;
    label: string;
}>();

const variant = computed(() => {
    switch (props.status) {
        case 'live':
            return 'default';
        case 'scheduled':
            return 'secondary';
        case 'draft':
            return 'outline';
        case 'closed':
            return 'secondary';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
});
</script>

<template>
    <Badge :variant="variant">{{ label }}</Badge>
</template>
