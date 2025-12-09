<script setup lang="ts">
import { index as permissionsIndex } from '@/actions/App/Http/Controllers/Admin/PermissionController';
import { index as usersIndex } from '@/actions/App/Http/Controllers/Admin/UserPermissionController';
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
import { usePermissions } from '@/composables/usePermissions';
import { apiDocs, dashboard, reports } from '@/routes';
import { type NavItem } from '@/types';
import { BookOpen, FileText, LayoutGrid, Shield, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const { canViewPermissions, canViewUsers } = usePermissions();

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Relatórios',
        href: reports(),
        icon: FileText,
    },
];

const apiNavItems: NavItem[] = [
    {
        title: 'Documentação',
        href: apiDocs(),
        icon: BookOpen,
    },
];

const adminNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (canViewPermissions.value) {
        items.push({
            title: 'Permissões',
            href: permissionsIndex(),
            icon: Shield,
        });
    }

    if (canViewUsers.value) {
        items.push({
            title: 'Usuários',
            href: usersIndex(),
            icon: Users,
        });
    }

    return items;
});

const showAdminSection = computed(() => adminNavItems.value.length > 0);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" class="cursor-default">
                        <AppLogo />
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Plataforma" />
            <NavMain :items="apiNavItems" label="API" />
            <NavMain
                v-if="showAdminSection"
                :items="adminNavItems"
                label="Administração"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
