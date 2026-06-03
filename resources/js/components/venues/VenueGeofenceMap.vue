<script setup lang="ts">
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { GeofenceVertex } from '@/types/admin';

type Props = {
    latitude?: string | number;
    longitude?: string | number;
    radiusMeters?: number | null;
    polygon?: GeofenceVertex[] | null;
};

const props = withDefaults(defineProps<Props>(), {
    latitude: 6.75,
    longitude: 125.35,
    radiusMeters: 150,
    polygon: null,
});

const mapContainer = ref<HTMLElement | null>(null);
const geofenceMode = ref<'radius' | 'polygon'>(
    props.polygon && props.polygon.length > 0 ? 'polygon' : 'radius',
);
const latitudeModel = ref(String(props.latitude ?? 6.75));
const longitudeModel = ref(String(props.longitude ?? 125.35));
const radiusModel = ref(String(props.radiusMeters ?? 150));
const polygonJson = ref(
    props.polygon && props.polygon.length > 0
        ? JSON.stringify(props.polygon)
        : '',
);

let map: L.Map | null = null;
let marker: L.Marker | null = null;
let circle: L.Circle | null = null;
let polygonLayer: L.Polygon | null = null;
const polygonVertices = ref<GeofenceVertex[]>([...(props.polygon ?? [])]);

function defaultIcon(): L.Icon {
    return L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        iconRetinaUrl:
            'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        shadowUrl:
            'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
    });
}

function parseCoord(value: string, fallback: number): number {
    const parsed = Number.parseFloat(value);
    return Number.isFinite(parsed) ? parsed : fallback;
}

function syncHiddenFields(): void {
    latitudeModel.value = marker
        ? marker.getLatLng().lat.toFixed(7)
        : latitudeModel.value;
    longitudeModel.value = marker
        ? marker.getLatLng().lng.toFixed(7)
        : longitudeModel.value;

    if (geofenceMode.value === 'polygon') {
        polygonJson.value =
            polygonVertices.value.length >= 3
                ? JSON.stringify(polygonVertices.value)
                : '';
    } else {
        polygonJson.value = '';
    }
}

function updateCircle(): void {
    if (!map || !marker || geofenceMode.value !== 'radius') {
        return;
    }

    const radius = Number.parseInt(radiusModel.value, 10) || 150;
    const center = marker.getLatLng();

    circle?.remove();
    circle = L.circle(center, { radius }).addTo(map);
}

function updatePolygonLayer(): void {
    if (!map) {
        return;
    }

    polygonLayer?.remove();

    if (polygonVertices.value.length >= 3) {
        const latLngs = polygonVertices.value.map(
            (v) => [v.lat, v.lng] as [number, number],
        );
        polygonLayer = L.polygon(latLngs, { color: '#2563eb' }).addTo(map);
        map.fitBounds(polygonLayer.getBounds(), { padding: [24, 24] });
    }
}

function setMarker(lat: number, lng: number): void {
    if (!map) {
        return;
    }

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: true, icon: defaultIcon() })
            .addTo(map)
            .on('dragend', () => {
                syncHiddenFields();
                updateCircle();
            });
    }

    syncHiddenFields();
    updateCircle();
}

function onMapClick(event: L.LeafletMouseEvent): void {
    if (geofenceMode.value === 'radius') {
        setMarker(event.latlng.lat, event.latlng.lng);
        return;
    }

    polygonVertices.value.push({
        lat: event.latlng.lat,
        lng: event.latlng.lng,
    });
    syncHiddenFields();
    updatePolygonLayer();
}

function clearPolygon(): void {
    polygonVertices.value = [];
    polygonLayer?.remove();
    polygonLayer = null;
    syncHiddenFields();
}

onMounted(() => {
    if (!mapContainer.value) {
        return;
    }

    const lat = parseCoord(latitudeModel.value, 6.75);
    const lng = parseCoord(longitudeModel.value, 125.35);

    map = L.map(mapContainer.value).setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    map.on('click', onMapClick);
    setMarker(lat, lng);

    if (geofenceMode.value === 'polygon' && polygonVertices.value.length >= 3) {
        updatePolygonLayer();
    }
});

onUnmounted(() => {
    map?.remove();
    map = null;
});

watch(radiusModel, () => {
    updateCircle();
    syncHiddenFields();
});

watch(geofenceMode, () => {
    if (geofenceMode.value === 'radius') {
        polygonVertices.value = [];
        polygonLayer?.remove();
        polygonLayer = null;
        polygonJson.value = '';
        updateCircle();
    } else {
        circle?.remove();
        circle = null;
    }
    syncHiddenFields();
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <Label class="sr-only">Geofence mode</Label>
            <button
                type="button"
                class="rounded-md border px-3 py-1.5 text-sm"
                :class="
                    geofenceMode === 'radius'
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border'
                "
                @click="geofenceMode = 'radius'"
            >
                Radius
            </button>
            <button
                type="button"
                class="rounded-md border px-3 py-1.5 text-sm"
                :class="
                    geofenceMode === 'polygon'
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border'
                "
                @click="geofenceMode = 'polygon'"
            >
                Polygon
            </button>
            <button
                v-if="geofenceMode === 'polygon'"
                type="button"
                class="text-sm text-muted-foreground underline"
                @click="clearPolygon"
            >
                Clear polygon
            </button>
        </div>

        <p class="text-sm text-muted-foreground">
            <template v-if="geofenceMode === 'radius'">
                Click the map to place the venue pin. Adjust the radius below.
            </template>
            <template v-else>
                Click the map to add polygon corners (minimum 3 points).
            </template>
        </p>

        <div
            ref="mapContainer"
            class="h-80 w-full overflow-hidden rounded-lg border border-sidebar-border/70"
        />

        <input type="hidden" name="latitude" :value="latitudeModel" />
        <input type="hidden" name="longitude" :value="longitudeModel" />
        <input type="hidden" name="geofence_polygon" :value="polygonJson" />

        <div
            v-if="geofenceMode === 'radius'"
            class="grid gap-2 sm:max-w-xs"
        >
            <Label for="geofence_radius_meters">Geofence radius (m)</Label>
            <Input
                id="geofence_radius_meters"
                v-model="radiusModel"
                name="geofence_radius_meters"
                type="number"
                min="10"
            />
        </div>
    </div>
</template>

<style>
.leaflet-container {
    z-index: 0;
}
</style>
