<script setup lang="ts">
import { AlertTriangle, CircleHelp, Info } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import {
    confirmState,
    resolveConfirm,
    setConfirmOpen,
    type ConfirmVariant,
} from '@/lib/confirm';

const iconConfig: Record<
    ConfirmVariant,
    { icon: typeof AlertTriangle; className: string; accentClass: string }
> = {
    destructive: {
        icon: AlertTriangle,
        className:
            'bg-destructive/10 text-destructive ring-destructive/20 dark:bg-destructive/15',
        accentClass: 'from-destructive to-destructive/40',
    },
    warning: {
        icon: CircleHelp,
        className:
            'bg-amber-500/10 text-amber-700 ring-amber-500/20 dark:text-amber-400',
        accentClass: 'from-amber-500 to-amber-500/40',
    },
    default: {
        icon: Info,
        className: 'bg-primary/10 text-primary ring-primary/20',
        accentClass: 'from-primary to-primary/40',
    },
};

const config = computed(
    () => iconConfig[confirmState.options.variant] ?? iconConfig.default,
);

const confirmButtonVariant = computed(() =>
    confirmState.options.variant === 'destructive'
        ? 'destructive'
        : confirmState.options.variant === 'warning'
          ? 'default'
          : 'default',
);
</script>

<template>
    <Dialog :open="confirmState.open" @update:open="setConfirmOpen">
        <DialogContent
            class="gap-0 overflow-hidden p-0 sm:max-w-md"
            :show-close-button="false"
        >
            <div
                :class="
                    cn(
                        'h-1 bg-gradient-to-r',
                        config.accentClass,
                    )
                "
            />

            <div class="space-y-5 p-6">
                <DialogHeader class="gap-4 text-left sm:text-left">
                    <div
                        :class="
                            cn(
                                'flex size-11 shrink-0 items-center justify-center rounded-xl ring-1 ring-inset',
                                config.className,
                            )
                        "
                    >
                        <component :is="config.icon" class="size-5" />
                    </div>

                    <div class="space-y-2">
                        <DialogTitle class="text-lg leading-snug">
                            {{ confirmState.options.title }}
                        </DialogTitle>
                        <DialogDescription
                            v-if="confirmState.options.description"
                            class="text-sm leading-relaxed"
                        >
                            {{ confirmState.options.description }}
                        </DialogDescription>
                    </div>
                </DialogHeader>

                <DialogFooter class="gap-2 sm:justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        @click="resolveConfirm(false)"
                    >
                        {{ confirmState.options.cancelLabel }}
                    </Button>
                    <Button
                        type="button"
                        :variant="confirmButtonVariant"
                        :class="
                            confirmState.options.variant === 'warning'
                                ? 'bg-amber-600 text-white hover:bg-amber-600/90 dark:bg-amber-500 dark:hover:bg-amber-500/90'
                                : undefined
                        "
                        @click="resolveConfirm(true)"
                    >
                        {{ confirmState.options.confirmLabel }}
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>
