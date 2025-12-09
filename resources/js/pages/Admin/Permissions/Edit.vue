<script setup lang="ts">
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/PermissionController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Permission } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

interface Props {
    permission: Permission;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Permissões',
        href: index().url,
    },
    {
        title: 'Editar Permissão',
        href: '#',
    },
];
</script>

<template>
    <Head title="Editar Permissão" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <Heading
                    title="Editar Permissão"
                    :description="`Editando: ${props.permission.name}`"
                />
            </div>

            <Card class="max-w-2xl">
                <Form
                    v-bind="update.form(props.permission.id)"
                    v-slot="{ errors, processing }"
                >
                    <CardContent class="space-y-6 pt-6">
                        <div class="grid gap-2">
                            <Label for="name">Nome</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="props.permission.name"
                                placeholder="Ex: Gerenciar Usuários"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="slug">Slug</Label>
                            <Input
                                id="slug"
                                name="slug"
                                :default-value="props.permission.slug"
                                placeholder="Ex: usuarios.gerenciar"
                            />
                            <p class="text-xs text-muted-foreground">
                                Identificador único. Use apenas letras
                                minúsculas, números, hífens, underlines e
                                pontos.
                            </p>
                            <InputError :message="errors.slug" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="group">Grupo</Label>
                            <Input
                                id="group"
                                name="group"
                                :default-value="props.permission.group"
                                placeholder="Ex: usuarios"
                                required
                            />
                            <InputError :message="errors.group" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Descrição</Label>
                            <Input
                                id="description"
                                name="description"
                                :default-value="
                                    props.permission.description ?? ''
                                "
                                placeholder="Ex: Permite gerenciar todos os usuários do sistema"
                            />
                            <InputError :message="errors.description" />
                        </div>
                    </CardContent>

                    <CardFooter class="flex justify-end gap-2">
                        <Link :href="index().url">
                            <Button variant="outline" type="button"
                                >Cancelar</Button
                            >
                        </Link>
                        <Button type="submit" :disabled="processing">
                            {{
                                processing
                                    ? 'Salvando...'
                                    : 'Atualizar Permissão'
                            }}
                        </Button>
                    </CardFooter>
                </Form>
            </Card>
        </div>
    </AppLayout>
</template>
