<script setup lang="ts">
import {
    create,
    destroy,
    index,
} from '@/actions/App/Http/Controllers/Admin/PermissionController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Permission } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    permissions: Permission[];
    groupedPermissions: Record<string, Permission[]>;
}

defineProps<Props>();

const { canManagePermissions } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Permissões',
        href: index().url,
    },
];

const deleteDialogOpen = ref(false);
const permissionToDelete = ref<Permission | null>(null);

function openDeleteDialog(permission: Permission) {
    permissionToDelete.value = permission;
    deleteDialogOpen.value = true;
}

function confirmDelete() {
    if (permissionToDelete.value) {
        router.delete(destroy.url(permissionToDelete.value.id), {
            onFinish: () => {
                deleteDialogOpen.value = false;
                permissionToDelete.value = null;
            },
        });
    }
}
</script>

<template>
    <Head title="Gerenciar Permissões" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <Heading
                    title="Permissões"
                    description="Gerencie as permissões do sistema"
                />
                <Link v-if="canManagePermissions" :href="create().url">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Nova Permissão
                    </Button>
                </Link>
            </div>

            <div
                v-if="Object.keys(groupedPermissions).length === 0"
                class="flex flex-1 items-center justify-center"
            >
                <div class="text-center">
                    <p class="text-muted-foreground">
                        Nenhuma permissão cadastrada.
                    </p>
                    <Link
                        v-if="canManagePermissions"
                        :href="create().url"
                        class="mt-4 inline-block"
                    >
                        <Button variant="outline">
                            <Plus class="mr-2 h-4 w-4" />
                            Criar primeira permissão
                        </Button>
                    </Link>
                </div>
            </div>

            <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="(perms, group) in groupedPermissions" :key="group">
                    <CardHeader>
                        <CardTitle class="text-lg capitalize">{{
                            group
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="permission in perms"
                            :key="permission.id"
                            class="flex items-center justify-between rounded-md border p-3"
                        >
                            <div>
                                <p class="font-medium">{{ permission.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ permission.slug }}
                                </p>
                                <p
                                    v-if="permission.description"
                                    class="mt-1 text-sm text-muted-foreground"
                                >
                                    {{ permission.description }}
                                </p>
                            </div>
                            <div v-if="canManagePermissions" class="flex gap-2">
                                <Link
                                    :href="`/admin/permissions/${permission.id}/edit`"
                                >
                                    <Button variant="ghost" size="icon">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                </Link>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    @click="openDeleteDialog(permission)"
                                >
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirmar exclusão</DialogTitle>
                    <DialogDescription>
                        Tem certeza que deseja excluir a permissão "{{
                            permissionToDelete?.name
                        }}"? Esta ação não pode ser desfeita.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="confirmDelete"
                        >Excluir</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
