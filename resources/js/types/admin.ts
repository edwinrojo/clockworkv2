export type ResourceAbilities = {
    viewAny: boolean;
    create: boolean;
};

export type AdminCan = {
    departments: ResourceAbilities;
    venues: ResourceAbilities;
};

export type RowAbilities = {
    update: boolean;
    delete: boolean;
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
