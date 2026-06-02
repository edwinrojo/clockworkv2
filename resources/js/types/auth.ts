import type { AdminCan } from './admin';

export type UserRole =
    | 'super_admin'
    | 'event_manager'
    | 'viewer'
    | 'employee';

export type User = {
    id: string;
    name: string;
    email: string;
    role: UserRole;
    employee_number: string | null;
    department_id: string | null;
    is_active: boolean;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
};

export type Auth = {
    user: User;
    can: AdminCan;
};

/* @chisel-passkeys */
export type Passkey = {
    id: string;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
