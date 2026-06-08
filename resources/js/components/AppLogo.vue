<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';

const page = usePage();
const appName = page.props.name as string;
</script>

<template>
    <BrandLogo size="sm" />
    <div class="ml-1 grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-semibold text-sidebar-foreground">{{
            appName
        }}</span>
        <span class="truncate text-xs text-sidebar-foreground/60"
            >Operations</span
        >
    </div>
</template>
