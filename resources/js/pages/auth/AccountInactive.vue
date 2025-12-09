<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AuthLayout
        title="Conta pendente de ativação"
        description="Sua conta ainda não foi ativada pelo administrador do sistema."
    >
        <Head title="Conta pendente de ativação" />

        <div class="space-y-6 text-center">
            <div
                class="bg-muted/50 border-muted rounded-lg border p-6 text-sm"
            >
                <p class="text-muted-foreground">
                    Seu cadastro foi realizado com sucesso, porém sua conta
                    precisa ser ativada por um administrador antes que você
                    possa acessar o sistema.
                </p>
                <p class="text-muted-foreground mt-4">
                    Por favor, entre em contato com o administrador do sistema
                    para solicitar a ativação da sua conta.
                </p>
            </div>

            <TextLink :href="logout()" as="button" class="mx-auto block">
                Sair
            </TextLink>
        </div>
    </AuthLayout>
</template>
