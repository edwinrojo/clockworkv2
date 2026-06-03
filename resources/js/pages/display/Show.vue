<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AlertCircle, Clock } from '@lucide/vue';
import QRCode from 'qrcode';
import { computed, onMounted, onUnmounted, ref } from 'vue';

type DisplayEvent = {
    title: string;
    venue_name: string | null;
    qr_rotation_seconds: number;
};

type PlaceholderMode = 'clock' | 'error';

const props = defineProps<{
    event: DisplayEvent;
    displaySecret: string;
    tokenUrl: string;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const placeholderMode = ref<PlaceholderMode | null>('clock');
const statusMessage = ref('');
const secondsRemaining = ref(0);
const serverTime = ref('');
let pollTimer: ReturnType<typeof setInterval> | null = null;
let clockTimer: ReturnType<typeof setInterval> | null = null;

const showQr = computed(() => placeholderMode.value === null);

function isWaitingMessage(message: string): boolean {
    const normalized = message.toLowerCase();

    return (
        normalized.includes('loading') ||
        normalized.includes('waiting') ||
        normalized.includes('not open')
    );
}

function setPlaceholder(message: string): void {
    if (isWaitingMessage(message)) {
        placeholderMode.value = 'clock';
        statusMessage.value = message.includes('not open')
            ? 'Waiting for check-in to open'
            : 'Preparing QR code';

        return;
    }

    placeholderMode.value = 'error';
    statusMessage.value = message;
}

async function fetchToken(): Promise<void> {
    try {
        const response = await fetch(props.tokenUrl, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (response.status === 403) {
            window.location.href = `/display/${props.displaySecret}/unlock`;

            return;
        }

        const data = await response.json();

        if (!data.active || !data.qr_token) {
            setPlaceholder(data.message ?? 'Check-in is not open.');
            secondsRemaining.value = 0;

            return;
        }

        placeholderMode.value = null;
        statusMessage.value = '';
        secondsRemaining.value = data.seconds_remaining ?? 0;

        if (canvasRef.value) {
            await QRCode.toCanvas(canvasRef.value, data.qr_token, {
                width: 480,
                margin: 2,
            });
        }
    } catch {
        setPlaceholder('Unable to load QR code.');
    }
}

function updateClock(): void {
    serverTime.value = new Date().toLocaleString(undefined, {
        weekday: 'long',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });

    if (secondsRemaining.value > 0) {
        secondsRemaining.value -= 1;
    }
}

onMounted(() => {
    fetchToken();
    pollTimer = setInterval(fetchToken, 3000);
    clockTimer = setInterval(updateClock, 1000);
    updateClock();
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
    if (clockTimer) {
        clearInterval(clockTimer);
    }
});
</script>

<template>
    <Head :title="event.title" />

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-slate-950 px-6 py-12 text-white"
    >
        <p class="text-sm tracking-widest text-slate-400 uppercase">
            Clockwork Check-in
        </p>
        <h1 class="mt-2 text-center text-3xl font-bold md:text-4xl">
            {{ event.title }}
        </h1>
        <p v-if="event.venue_name" class="mt-1 text-slate-400">
            {{ event.venue_name }}
        </p>
        <p class="mt-4 font-mono text-lg text-slate-300">{{ serverTime }}</p>

        <div
            class="mt-10 flex flex-col items-center rounded-2xl bg-white p-6 shadow-2xl"
        >
            <canvas v-show="showQr" ref="canvasRef" />

            <div
                v-if="!showQr"
                class="flex h-[480px] w-[480px] max-w-full flex-col items-center justify-center px-8"
            >
                <div
                    v-if="placeholderMode === 'clock'"
                    class="flex flex-col items-center gap-6"
                >
                    <div
                        class="relative flex size-40 items-center justify-center"
                    >
                        <span
                            class="absolute inset-0 animate-ping rounded-full bg-slate-200/80"
                            aria-hidden="true"
                        />
                        <span
                            class="absolute inset-3 rounded-full bg-slate-100"
                            aria-hidden="true"
                        />
                        <Clock
                            class="relative size-20 text-slate-600 motion-safe:animate-[spin_2.5s_linear_infinite]"
                            aria-hidden="true"
                        />
                    </div>
                    <p class="text-center text-base font-medium text-slate-600">
                        {{ statusMessage }}
                    </p>
                </div>

                <div
                    v-else-if="placeholderMode === 'error'"
                    class="flex flex-col items-center gap-4 text-center"
                >
                    <AlertCircle
                        class="size-16 text-amber-600"
                        aria-hidden="true"
                    />
                    <p class="text-lg text-slate-700">
                        {{ statusMessage }}
                    </p>
                </div>
            </div>
        </div>

        <p v-if="showQr" class="mt-6 text-center text-lg text-slate-300">
            Scan with the Clockwork mobile app
        </p>
        <p
            v-if="secondsRemaining > 0"
            class="mt-2 font-mono text-2xl text-emerald-400"
        >
            Refreshes in {{ secondsRemaining }}s
        </p>
    </div>
</template>
