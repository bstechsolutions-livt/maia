<script setup lang="ts">
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/UserPermissionController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Permission, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    user: User;
    permissions: Permission[];
    groupedPermissions: Record<string, Permission[]>;
    userPermissionIds: number[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuários',
        href: index().url,
    },
    {
        title: `Permissões de ${props.user.name}`,
        href: '#',
    },
];

const selectedPermissions = ref<number[]>([...props.userPermissionIds]);
const processing = ref(false);

function isChecked(permissionId: number): boolean {
    return selectedPermissions.value.includes(permissionId);
}

function togglePermission(permissionId: number, checked: boolean) {
    if (checked) {
        if (!selectedPermissions.value.includes(permissionId)) {
            selectedPermissions.value.push(permissionId);
        }
    } else {
        const index = selectedPermissions.value.indexOf(permissionId);
        if (index > -1) {
            selectedPermissions.value.splice(index, 1);
        }
    }
}

function toggleGroup(group: string, checked: boolean) {
    const groupPermissions = props.groupedPermissions[group] || [];
    for (const permission of groupPermissions) {
        togglePermission(permission.id, checked);
    }
}

function isGroupChecked(group: string): boolean {
    const groupPermissions = props.groupedPermissions[group] || [];
    return groupPermissions.every((p) =>
        selectedPermissions.value.includes(p.id),
    );
}

function isGroupIndeterminate(group: string): boolean {
    const groupPermissions = props.groupedPermissions[group] || [];
    const checked = groupPermissions.filter((p) =>
        selectedPermissions.value.includes(p.id),
    ).length;
    return checked > 0 && checked < groupPermissions.length;
}

function submit() {
    processing.value = true;
    router.put(
        update.url(props.user.id),
        { permissions: selectedPermissions.value },
        {
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="`Permissões de ${props.user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <Heading
                    :title="`Permissões de ${props.user.name}`"
                    description="Selecione as permissões que deseja atribuir a este usuário"
                />
            </div>

            <div
                v-if="permissions.length === 0"
                class="flex flex-1 items-center justify-center"
            >
                <div class="text-center">
                    <p class="text-muted-foreground">
                        Nenhuma permissão cadastrada no sistema.
                    </p>
                    <Link
                        href="/admin/permissions/create"
                        class="mt-4 inline-block"
                    >
                        <Button variant="outline"
                            >Criar primeira permissão</Button
                        >
                    </Link>
                </div>
            </div>

            <div v-else>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="(perms, group) in groupedPermissions"
                        :key="group"
                    >
                        <CardHeader class="pb-3">
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    :id="`group-${group}`"
                                    :model-value="isGroupChecked(group)"
                                    :indeterminate="isGroupIndeterminate(group)"
                                    @update:model-value="
                                        (checked: boolean | 'indeterminate') =>
                                            toggleGroup(group, checked === true)
                                    "
                                />
                                <Label
                                    :for="`group-${group}`"
                                    class="cursor-pointer"
                                >
                                    <CardTitle class="text-lg capitalize">{{
                                        group
                                    }}</CardTitle>
                                </Label>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div
                                v-for="permission in perms"
                                :key="permission.id"
                                class="flex items-start gap-2"
                            >
                                <Checkbox
                                    :id="`permission-${permission.id}`"
                                    :model-value="isChecked(permission.id)"
                                    @update:model-value="
                                        (checked: boolean | 'indeterminate') =>
                                            togglePermission(
                                                permission.id,
                                                checked === true,
                                            )
                                    "
                                />
                                <div class="flex-1">
                                    <Label
                                        :for="`permission-${permission.id}`"
                                        class="cursor-pointer"
                                    >
                                        {{ permission.name }}
                                    </Label>
                                    <p
                                        v-if="permission.description"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ permission.description }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card class="mt-6 max-w-2xl">
                    <CardFooter class="flex justify-end gap-2 pt-6">
                        <Link :href="index().url">
                            <Button variant="outline" type="button"
                                >Cancelar</Button
                            >
                        </Link>
                        <Button @click="submit" :disabled="processing">
                            {{
                                processing ? 'Salvando...' : 'Salvar Permissões'
                            }}
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
