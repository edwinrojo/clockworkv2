<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    BarChart3,
    Calendar,
    ClipboardList,
    LayoutGrid,
    MapPin,
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
import { index as usersIndex } from '@/routes/users';
import { index as venuesIndex } from '@/routes/venues';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (page.props.auth.can.events.viewAny) {
        items.push({
            title: 'Events',
            href: eventsIndex(),
            icon: Calendar,
        });
        items.push({
            title: 'Reports',
            href: reportsIndex(),
            icon: BarChart3,
        });
        items.push({
            title: 'Audit log',
            href: auditIndex(),
            icon: ClipboardList,
        });
    }

    return items;
});

const organizationNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];
    const can = page.props.auth.can;

    if (can.departments.viewAny) {
        items.push({
            title: 'Departments',
            href: departmentsIndex(),
            icon: Building2,
        });
    }

    if (can.venues.viewAny) {
        items.push({
            title: 'Venues',
            href: venuesIndex(),
            icon: MapPin,
        });
    }

    if (can.users.viewAny) {
        items.push({
            title: 'Users',
            href: usersIndex(),
            icon: Users,
        });
    }

    return items;
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
            <NavMain :items="mainNavItems" />
            <NavMain
                v-if="organizationNavItems.length > 0"
                label="Organization"
                :items="organizationNavItems"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length > 0" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
