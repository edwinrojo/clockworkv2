<script setup lang="ts">
type Props = {
    size?: 'sm' | 'md' | 'lg';
    showText?: boolean;
    variant?: 'light' | 'dark';
};

withDefaults(defineProps<Props>(), {
    size: 'md',
    showText: false,
    variant: 'dark',
});

const sizeClasses = {
    sm: 'h-8 w-8',
    md: 'h-10 w-10',
    lg: 'h-14 w-14',
};
</script>

<template>
    <div class="flex items-center gap-3">
        <img
            src="/brand/logo.png"
            alt="Clockwork"
            :class="['object-contain', sizeClasses[size]]"
        />
        <div v-if="showText" class="grid text-left leading-tight">
            <span
                class="font-semibold tracking-tight"
                :class="variant === 'light' ? 'text-white' : 'text-foreground'"
            >
                Clockwork
            </span>
            <span
                class="text-xs"
                :class="
                    variant === 'light'
                        ? 'text-white/70'
                        : 'text-muted-foreground'
                "
            >
                Attendance & events
            </span>
        </div>
    </div>
</template>
