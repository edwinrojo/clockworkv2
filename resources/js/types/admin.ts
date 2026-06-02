export type ResourceAbilities = {
    viewAny: boolean;
    create: boolean;
};

export type AdminCan = {
    departments: ResourceAbilities;
    venues: ResourceAbilities;
    events: ResourceAbilities;
};

export type SelectOption = {
    value: string;
    label: string;
};

export type VenueOption = {
    id: string;
    name: string;
};

export type RowAbilities = {
    update: boolean;
    delete: boolean;
};

export type EventRow = {
    id: string;
    title: string;
    description: string | null;
    venue_id: string;
    venue_name: string | null;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    starts_at: string;
    ends_at: string;
    check_in_opens_at: string | null;
    check_in_closes_at: string | null;
    qr_rotation_seconds: number;
    duplicate_policy: string;
    duplicate_policy_label: string;
    sessions_count: number;
    attendances_count: number;
    can: RowAbilities;
};

export type EventFormOptions = {
    venues: VenueOption[];
    types: SelectOption[];
    statuses: SelectOption[];
    duplicatePolicies: SelectOption[];
};

export type EventEditPageProps = EventFormOptions & {
    event: EventRow;
};

export type DepartmentRow = {
    id: string;
    name: string;
    code: string | null;
    parent_id: string | null;
    parent_name: string | null;
    is_active: boolean;
    users_count: number;
    children_count: number;
    can: RowAbilities;
};

export type DepartmentOption = {
    id: string;
    name: string;
};

export type VenueRow = {
    id: string;
    name: string;
    address: string | null;
    latitude: string;
    longitude: string;
    geofence_radius_meters: number | null;
    accuracy_buffer_meters: number;
    is_active: boolean;
    events_count: number;
    can: RowAbilities;
};
