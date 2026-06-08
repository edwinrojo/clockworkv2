<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Calendar,
    MapPin,
    QrCode,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import BrandLogo from '@/components/BrandLogo.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
import { register } from '@/routes';

const features = [
    {
        icon: QrCode,
        title: 'Dynamic QR check-in',
        description:
            'Rotating venue codes prevent screenshot replay and keep sessions secure.',
    },
    {
        icon: MapPin,
        title: 'Geofence validation',
        description:
            'GPS verification ensures employees are physically present at the event.',
    },
    {
        icon: Calendar,
        title: 'Multi-day events',
        description:
            'Schedule per-date check-in windows with automatic session start.',
    },
    {
        icon: Users,
        title: 'Live roster tracking',
        description:
            'See who checked in, who is missing, and who arrived late in real time.',
    },
];
</script>

<template>
    <Head title="Clockwork — Event Attendance" />

    <div class="brand-gradient-soft min-h-screen">
        <header class="border-b border-border/50 bg-background/70 backdrop-blur-md">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4"
            >
                <Link :href="dashboard()">
                    <BrandLogo size="md" show-text />
                </Link>
                <nav class="flex items-center gap-3">
                    <template v-if="$page.props.auth.user">
                        <Button as-child>
                            <Link :href="dashboard()">Dashboard</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button variant="ghost" as-child>
                            <Link :href="login()">Log in</Link>
                        </Button>
                        <Button as-child>
                            <Link :href="register()">Get started</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-6xl px-6 pb-20 pt-16 lg:pt-24">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div class="space-y-8">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-4 py-1.5 text-sm font-medium text-primary"
                        >
                            <ShieldCheck class="size-4" />
                            Government-grade attendance
                        </div>
                        <h1
                            class="text-4xl font-bold tracking-tight text-balance text-foreground sm:text-5xl lg:text-6xl"
                        >
                            Event attendance,
                            <span class="text-primary">simplified</span>
                        </h1>
                        <p
                            class="max-w-xl text-lg leading-relaxed text-muted-foreground"
                        >
                            Clockwork replaces long biometric queues with
                            fast QR check-in, live operations dashboards, and
                            auditable records for convocations and trainings.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <Button size="lg" as-child>
                                <Link :href="$page.props.auth.user ? dashboard() : login()">
                                    {{
                                        $page.props.auth.user
                                            ? 'Open dashboard'
                                            : 'Sign in to dashboard'
                                    }}
                                </Link>
                            </Button>
                            <Button size="lg" variant="outline" as-child>
                                <Link :href="register()">Create account</Link>
                            </Button>
                        </div>
                    </div>

                    <div class="relative">
                        <div
                            class="brand-gradient absolute -inset-4 rounded-3xl opacity-20 blur-2xl"
                        />
                        <div
                            class="glass-card relative overflow-hidden p-8 lg:p-10"
                        >
                            <div class="mb-6 flex items-center justify-between">
                                <span class="text-sm font-medium text-muted-foreground"
                                    >Live session</span
                                >
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400"
                                >
                                    <span
                                        class="size-1.5 animate-pulse rounded-full bg-emerald-500"
                                    />
                                    Active
                                </span>
                            </div>
                            <p class="text-2xl font-semibold">
                                Monday Convocation
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Provincial Capitol · 248 checked in
                            </p>
                            <div class="mt-8 grid grid-cols-3 gap-4">
                                <div
                                    class="rounded-xl bg-muted/60 p-4 text-center"
                                >
                                    <p class="text-2xl font-bold text-primary">
                                        312
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Expected
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl bg-muted/60 p-4 text-center"
                                >
                                    <p
                                        class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"
                                    >
                                        248
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Present
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl bg-muted/60 p-4 text-center"
                                >
                                    <p
                                        class="text-2xl font-bold text-amber-600 dark:text-amber-400"
                                    >
                                        12
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Late
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-t border-border/50 bg-card/50 py-20">
                <div class="mx-auto max-w-6xl px-6">
                    <div class="mb-12 text-center">
                        <h2 class="text-3xl font-bold tracking-tight">
                            Built for field operations
                        </h2>
                        <p class="mx-auto mt-3 max-w-2xl text-muted-foreground">
                            From scheduling events to running live check-in at
                            the venue — coordinators stay in control.
                        </p>
                    </div>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="feature in features"
                            :key="feature.title"
                            class="admin-card group p-6 transition-shadow hover:shadow-lg"
                        >
                            <div
                                class="mb-4 flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground"
                            >
                                <component :is="feature.icon" class="size-5" />
                            </div>
                            <h3 class="font-semibold">{{ feature.title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                                {{ feature.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer
            class="border-t border-border/50 bg-background py-8 text-center text-sm text-muted-foreground"
        >
            <p>Clockwork · Provincial Government Digital Attendance</p>
        </footer>
    </div>
</template>
