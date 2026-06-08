<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
};

defineProps<Props>();
</script>

<template>
    <section class="admin-card space-y-5 p-5 md:p-6">
        <div>
            <h2 class="text-base font-semibold">{{ title }}</h2>
            <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div class="space-y-4">
            <slot />
        </div>
    </section>
</template>
