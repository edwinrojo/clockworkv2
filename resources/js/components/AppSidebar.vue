<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    BarChart3,
    Calendar,
    ClipboardList,
    LayoutGrid,
    MapPin,
    Smartphone,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as auditIndex } from '@/routes/audit-log';
import { index as departmentsIndex } from '@/routes/departments';
import { index as eventsIndex } from '@/routes/events';
import { index as reportsIndex } from '@/routes/reports';
import { index as deviceRequestsIndex } from '@/routes/device-change-requests';
import { index as usersIndex } from '@/routes/users';
import { index as venuesIndex } from '@/routes/venues';
import type { NavItem } from '@/types';

type NavGroup = {
    label: string;
    items: NavItem[];
};

const page = usePage();

const navGroups = computed<NavGroup[]>(() => {
    const can = page.props.auth.can;
    const groups: NavGroup[] = [];

    groups.push({
        label: 'Overview',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ],
    });

    if (can.events.viewAny) {
        groups.push({
            label: 'Operations',
            items: [
                {
                    title: 'Events',
                    href: eventsIndex(),
                    icon: Calendar,
                },
                {
                    title: 'Reports',
                    href: reportsIndex(),
                    icon: BarChart3,
                },
            ],
        });
    }

    const organizationItems: NavItem[] = [];

    if (can.departments.viewAny) {
        organizationItems.push({
            title: 'Departments',
            href: departmentsIndex(),
            icon: Building2,
        });
    }

    if (can.venues.viewAny) {
        organizationItems.push({
            title: 'Venues',
            href: venuesIndex(),
            icon: MapPin,
        });
    }

    if (organizationItems.length > 0) {
        groups.push({
            label: 'Organization',
            items: organizationItems,
        });
    }

    const administrationItems: NavItem[] = [];

    if (can.users.viewAny) {
        administrationItems.push({
            title: 'Users',
            href: usersIndex(),
            icon: Users,
        });
        administrationItems.push({
            title: 'Device requests',
            href: deviceRequestsIndex(),
            icon: Smartphone,
            badge:
                page.props.auth.pending_device_change_requests_count > 0
                    ? String(
                          page.props.auth
                              .pending_device_change_requests_count,
                      )
                    : undefined,
        });
    }

    if (can.events.viewAny) {
        administrationItems.push({
            title: 'Audit log',
            href: auditIndex(),
            icon: ClipboardList,
        });
    }

    if (administrationItems.length > 0) {
        groups.push({
            label: 'Administration',
            items: administrationItems,
        });
    }

    return groups;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                v-for="group in navGroups"
                :key="group.label"
                :label="group.label"
                :items="group.items"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length > 0" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
