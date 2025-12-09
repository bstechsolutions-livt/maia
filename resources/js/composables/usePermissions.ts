import type { AppPageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage<AppPageProps>();

    const permissions = computed(() => page.props.auth?.permissions ?? []);

    function hasPermission(permission: string): boolean {
        return permissions.value.includes(permission);
    }

    function hasAnyPermission(...permissionList: string[]): boolean {
        return permissionList.some((p) => permissions.value.includes(p));
    }

    function hasAllPermissions(...permissionList: string[]): boolean {
        return permissionList.every((p) => permissions.value.includes(p));
    }

    // Permissões específicas como computed para uso direto no template
    const canViewPermissions = computed(() =>
        hasAnyPermission('admin.permissions.view', 'admin.permissions.manage'),
    );
    const canManagePermissions = computed(() =>
        hasPermission('admin.permissions.manage'),
    );
    const canViewUsers = computed(() =>
        hasAnyPermission('admin.users.view', 'admin.users.manage'),
    );
    const canManageUsers = computed(() => hasPermission('admin.users.manage'));

    return {
        permissions,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        canViewPermissions,
        canManagePermissions,
        canViewUsers,
        canManageUsers,
    };
}
