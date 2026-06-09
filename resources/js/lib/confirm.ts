import { reactive, readonly } from 'vue';

export type ConfirmVariant = 'default' | 'destructive' | 'warning';

export type ConfirmOptions = {
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: ConfirmVariant;
};

type ConfirmState = {
    open: boolean;
    options: Required<ConfirmOptions>;
    resolve: ((value: boolean) => void) | null;
};

const defaultOptions: Required<ConfirmOptions> = {
    title: '',
    description: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    variant: 'default',
};

const state = reactive<ConfirmState>({
    open: false,
    options: { ...defaultOptions },
    resolve: null,
});

export const confirmState = readonly(state);

export function confirm(options: ConfirmOptions): Promise<boolean> {
    return new Promise((resolve) => {
        state.options = {
            ...defaultOptions,
            ...options,
        };
        state.resolve = resolve;
        state.open = true;
    });
}

export function resolveConfirm(value: boolean): void {
    state.resolve?.(value);
    state.open = false;
    state.resolve = null;
}

export function setConfirmOpen(open: boolean): void {
    if (!open) {
        resolveConfirm(false);
    }
}
