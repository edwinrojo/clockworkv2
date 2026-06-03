<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/display/unlock';

defineProps<{
    eventTitle: string;
    displaySecret: string;
}>();
</script>

<template>
    <Head :title="`${eventTitle} — Display unlock`" />

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-slate-950 px-6 text-white"
    >
        <div
            class="w-full max-w-sm rounded-2xl bg-white p-8 text-slate-900 shadow-2xl"
        >
            <div class="mb-6 flex flex-col items-center gap-3 text-center">
                <div
                    class="flex size-14 items-center justify-center rounded-full bg-slate-100"
                >
                    <Lock class="size-7 text-slate-600" aria-hidden="true" />
                </div>
                <h1 class="text-xl font-semibold">{{ eventTitle }}</h1>
                <p class="text-sm text-slate-500">
                    Enter the 4-digit PIN to open the check-in display.
                </p>
            </div>

            <Form
                :action="store.url(displaySecret)"
                method="post"
                class="space-y-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="pin">Display PIN</Label>
                    <Input
                        id="pin"
                        name="pin"
                        type="password"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="4"
                        required
                        autocomplete="off"
                        placeholder="••••"
                        class="text-center text-2xl tracking-[0.5em]"
                    />
                    <InputError :message="errors.pin" />
                </div>

                <Button type="submit" class="w-full" :disabled="processing">
                    Unlock display
                </Button>
            </Form>
        </div>
    </div>
</template>
