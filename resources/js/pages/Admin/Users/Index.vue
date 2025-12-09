<script setup lang="ts">
import { toggleActive } from '@/actions/App/Http/Controllers/Admin/UserController';
import {
    edit,
    index,
} from '@/actions/App/Http/Controllers/Admin/UserPermissionController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Shield, Users } from 'lucide-vue-next';

interface Props {
    users: User[];
}

defineProps<Props>();

const { canManageUsers } = usePermissions();
const page = usePage();
const currentUserId = page.props.auth.user?.id;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuários',
        href: index().url,
    },
];

function handleToggleActive(user: User) {
    router.patch(
        toggleActive.url(user.id),
        {},
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head title="Gerenciar Usuários" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Usuários"
                description="Gerencie as permissões dos usuários do sistema"
            />

            <div
                v-if="users.length === 0"
                class="flex flex-1 items-center justify-center"
            >
                <div class="text-center">
                    <Users class="mx-auto h-12 w-12 text-muted-foreground" />
                    <p class="mt-4 text-muted-foreground">
                        Nenhum usuário cadastrado.
                    </p>
                </div>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="user in users"
                    :key="user.id"
                    :class="{ 'opacity-60': !Boolean(user.is_active) }"
                >
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <CardTitle class="text-base">{{
                                    user.name
                                }}</CardTitle>
                                <Badge
                                    v-if="!Boolean(user.is_active)"
                                    variant="destructive"
                                    class="text-xs"
                                >
                                    Inativo
                                </Badge>
                            </div>
                            <div class="flex items-center gap-2">
                                <Switch
                                    v-if="
                                        canManageUsers &&
                                        user.id !== currentUserId
                                    "
                                    :default-value="Boolean(user.is_active)"
                                    @update:model-value="
                                        handleToggleActive(user)
                                    "
                                />
                                <Link
                                    v-if="canManageUsers"
                                    :href="edit.url(user.id)"
                                >
                                    <Button variant="outline" size="sm">
                                        <Shield class="mr-2 h-4 w-4" />
                                        Permissões
                                    </Button>
                                </Link>
                            </div>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ user.email }}
                        </p>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-1">
                            <template
                                v-if="
                                    user.permissions &&
                                    user.permissions.length > 0
                                "
                            >
                                <Badge
                                    v-for="permission in user.permissions.slice(
                                        0,
                                        3,
                                    )"
                                    :key="permission.id"
                                    variant="secondary"
                                    class="text-xs"
                                >
                                    {{ permission.name }}
                                </Badge>
                                <Badge
                                    v-if="user.permissions.length > 3"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    +{{ user.permissions.length - 3 }} mais
                                </Badge>
                            </template>
                            <span v-else class="text-sm text-muted-foreground">
                                Sem permissões atribuídas
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
