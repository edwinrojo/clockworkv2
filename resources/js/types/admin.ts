export type ResourceAbilities = {
    viewAny: boolean;
    create: boolean;
};

export type AdminCan = {
    departments: ResourceAbilities;
    venues: ResourceAbilities;
    events: ResourceAbilities;
    users: ResourceAbilities;
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

export type EventRowAbilities = RowAbilities & {
    manageSession: boolean;
    viewAttendances: boolean;
};

export type EventScheduleRow = {
    event_date: string;
    check_in_time: string;
    check_out_time: string;
    late_cutoff_time: string;
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
    is_multi_day: boolean;
    schedule: EventScheduleRow[];
    starts_at: string;
    ends_at: string;
    qr_rotation_seconds: number;
    duplicate_policy: string;
    duplicate_policy_label: string;
    sessions_count: number;
    attendances_count: number;
    can: EventRowAbilities;
};

export type EventTodaySchedule = EventScheduleRow & {
    check_in_opens_at: string;
    late_cutoff_at: string;
};

export type EventLiveSession = {
    id: string;
    status: string;
    status_label: string;
    started_at: string;
    started_by_name: string | null;
};

export type EventLiveAttendance = {
    id: string;
    employee_name: string;
    employee_number: string | null;
    checked_in_at: string;
    source: string;
    status: string;
    status_label?: string;
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

export type RegisteredDevice = {
    device_name: string | null;
    device_model: string | null;
    platform: string | null;
    os_version: string | null;
    approved_at: string | null;
    approved_by_name: string | null;
    last_seen_at: string | null;
};

export type PendingDeviceChange = {
    id: string;
    device_name: string | null;
    device_model: string | null;
    platform: string | null;
    os_version: string | null;
    reason: string | null;
    created_at: string;
};

export type UserRowAbilities = RowAbilities & {
    revokeTokens?: boolean;
    managePassword?: boolean;
    unlinkDevice?: boolean;
    reviewDeviceChange?: boolean;
};

export type UserRow = {
    id: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    suffix: string | null;
    name: string;
    email: string;
    role: string;
    role_label: string;
    employee_number: string | null;
    department_id: string | null;
    department_name: string | null;
    is_active: boolean;
    email_verified_at: string | null;
    registered_device?: RegisteredDevice | null;
    pending_device_change?: PendingDeviceChange | null;
    can: UserRowAbilities;
};

export type UserFormOptions = {
    departments: DepartmentOption[];
    roles: SelectOption[];
};

export type UserEditPageProps = UserFormOptions & {
    managedUser: UserRow;
};

export type GeofenceVertex = {
    lat: number;
    lng: number;
};

export type VenueRow = {
    id: string;
    name: string;
    address: string | null;
    latitude: string;
    longitude: string;
    geofence_radius_meters: number | null;
    geofence_polygon: GeofenceVertex[] | null;
    accuracy_buffer_meters: number;
    is_active: boolean;
    events_count: number;
    can: RowAbilities;
};
