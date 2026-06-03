<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

const props = defineProps<{
    role: string;
    label: string;
}>();

const variant = computed(() => {
    switch (props.role) {
        case 'super_admin':
            return 'default';
        case 'event_manager':
            return 'secondary';
        case 'viewer':
            return 'outline';
        case 'employee':
            return 'secondary';
        default:
            return 'outline';
    }
});
</script>

<template>
    <Badge :variant="variant">{{ label }}</Badge>
</template>
