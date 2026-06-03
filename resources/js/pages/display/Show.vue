<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import QRCode from 'qrcode';
import { onMounted, onUnmounted, ref } from 'vue';

type DisplayEvent = {
    title: string;
    venue_name: string | null;
    qr_rotation_seconds: number;
};

const props = defineProps<{
    event: DisplayEvent;
    displaySecret: string;
    tokenUrl: string;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const statusMessage = ref('Loading QR code…');
const secondsRemaining = ref(0);
const serverTime = ref('');
let pollTimer: ReturnType<typeof setInterval> | null = null;
let clockTimer: ReturnType<typeof setInterval> | null = null;

async function fetchToken(): Promise<void> {
    try {
        const response = await fetch(props.tokenUrl, {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();

        if (!data.active || !data.qr_token) {
            statusMessage.value = data.message ?? 'Check-in is not open.';
            secondsRemaining.value = 0;
            return;
        }

        statusMessage.value = '';
        secondsRemaining.value = data.seconds_remaining ?? 0;

        if (canvasRef.value) {
            await QRCode.toCanvas(canvasRef.value, data.qr_token, {
                width: 480,
                margin: 2,
            });
        }
    } catch {
        statusMessage.value = 'Unable to load QR code.';
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
        <p class="text-sm uppercase tracking-widest text-slate-400">
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
            <canvas v-show="!statusMessage" ref="canvasRef" />
            <p
                v-if="statusMessage"
                class="flex h-[480px] w-[480px] max-w-full items-center justify-center text-center text-lg text-slate-700"
            >
                {{ statusMessage }}
            </p>
        </div>

        <p
            v-if="!statusMessage"
            class="mt-6 text-center text-lg text-slate-300"
        >
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
