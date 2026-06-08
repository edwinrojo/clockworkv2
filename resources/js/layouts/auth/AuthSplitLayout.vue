<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative grid min-h-dvh lg:grid-cols-2">
        <div
            class="brand-gradient relative hidden flex-col justify-between overflow-hidden p-10 text-white lg:flex"
        >
            <div
                class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-32 -left-16 h-80 w-80 rounded-full bg-black/20 blur-3xl"
            />

            <Link :href="home()" class="relative z-10">
                <BrandLogo size="lg" show-text variant="light" />
            </Link>

            <div class="relative z-10 max-w-md space-y-6">
                <h2 class="text-3xl font-semibold tracking-tight text-balance">
                    Smart attendance for government events
                </h2>
                <p class="text-base leading-relaxed text-white/80">
                    Coordinate convocations, run live check-in sessions, and
                    track attendance with geofenced QR verification.
                </p>
                <ul class="space-y-3 text-sm text-white/85">
                    <li class="flex items-center gap-3">
                        <span
                            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-white/15 text-xs font-semibold"
                            >1</span
                        >
                        Rotating venue QR codes
                    </li>
                    <li class="flex items-center gap-3">
                        <span
                            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-white/15 text-xs font-semibold"
                            >2</span
                        >
                        GPS geofence validation
                    </li>
                    <li class="flex items-center gap-3">
                        <span
                            class="flex size-6 shrink-0 items-center justify-center rounded-full bg-white/15 text-xs font-semibold"
                            >3</span
                        >
                        Real-time roster & reports
                    </li>
                </ul>
            </div>

            <p class="relative z-10 text-xs text-white/50">
                Provincial Government · Digital attendance platform
            </p>
        </div>

        <div
            class="brand-gradient-soft flex flex-col items-center justify-center px-6 py-12 lg:px-12"
        >
            <div class="mb-8 lg:hidden">
                <Link :href="home()">
                    <BrandLogo size="md" show-text />
                </Link>
            </div>

            <div class="w-full max-w-md">
                <div
                    class="rounded-2xl bg-card p-8 shadow-lg shadow-black/10 dark:shadow-black/30"
                >
                    <div class="mb-8 space-y-2 text-center lg:text-left">
                        <h1
                            v-if="title"
                            class="text-2xl font-semibold tracking-tight text-foreground"
                        >
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-sm text-muted-foreground"
                        >
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
