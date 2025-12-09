<script setup lang="ts">
import { index } from '@/actions/App/Http/Controllers/ApiDocsController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    Clock,
    Copy,
    Key,
    Loader2,
    Lock,
    Play,
    Unlock,
    X,
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

interface Parameter {
    name: string;
    type: string;
    required: boolean;
    description: string;
}

interface ResponseExample {
    code: number;
    example: Record<string, unknown>;
}

interface Endpoint {
    name: string;
    method: string;
    path: string;
    description: string;
    auth: boolean;
    parameters: Parameter[];
    response: {
        success: ResponseExample;
        error?: ResponseExample;
    };
}

interface EndpointGroup {
    group: string;
    endpoints: Endpoint[];
}

interface Token {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string;
}

interface Props {
    endpoints: EndpointGroup[];
    tokens: Token[];
    user: {
        id: number;
        name: string;
        email: string;
    };
    baseUrl: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documentação API',
        href: index().url,
    },
];

const methodColors: Record<string, string> = {
    GET: 'bg-blue-500',
    POST: 'bg-green-500',
    PUT: 'bg-yellow-500',
    PATCH: 'bg-orange-500',
    DELETE: 'bg-red-500',
};

// Estado para cada endpoint
const endpointStates = reactive<
    Record<
        string,
        {
            isOpen: boolean;
            isLoading: boolean;
            response: string | null;
            responseStatus: number | null;
            responseTime: number | null;
            error: string | null;
            params: Record<string, string>;
        }
    >
>({});

// Token selecionado
const selectedToken = ref<string>('');
const copiedToken = ref(false);

// Inicializar estados dos endpoints
function getEndpointKey(group: string, endpoint: Endpoint): string {
    return `${group}-${endpoint.method}-${endpoint.path}`;
}

function getState(group: string, endpoint: Endpoint) {
    const key = getEndpointKey(group, endpoint);
    if (!endpointStates[key]) {
        endpointStates[key] = {
            isOpen: false,
            isLoading: false,
            response: null,
            responseStatus: null,
            responseTime: null,
            error: null,
            params: {},
        };
        // Pré-preencher com dados do usuário
        endpoint.parameters.forEach((param) => {
            if (param.name === 'email') {
                endpointStates[key].params[param.name] = props.user.email;
            } else if (param.name === 'device_name') {
                endpointStates[key].params[param.name] = 'API Docs Test';
            } else {
                endpointStates[key].params[param.name] = '';
            }
        });
    }
    return endpointStates[key];
}

function toggleEndpoint(group: string, endpoint: Endpoint) {
    const state = getState(group, endpoint);
    state.isOpen = !state.isOpen;
}

async function executeRequest(group: string, endpoint: Endpoint) {
    const state = getState(group, endpoint);
    state.isLoading = true;
    state.response = null;
    state.responseStatus = null;
    state.responseTime = null;
    state.error = null;

    const startTime = performance.now();

    try {
        const url = `${props.baseUrl}${endpoint.path}`;
        const headers: Record<string, string> = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        };

        if (endpoint.auth && selectedToken.value) {
            headers['Authorization'] = `Bearer ${selectedToken.value}`;
        }

        const options: RequestInit = {
            method: endpoint.method,
            headers,
        };

        if (
            endpoint.method !== 'GET' &&
            Object.keys(state.params).length > 0
        ) {
            options.body = JSON.stringify(state.params);
        }

        const response = await fetch(url, options);
        const endTime = performance.now();

        state.responseTime = Math.round(endTime - startTime);
        state.responseStatus = response.status;

        const data = await response.json();
        state.response = JSON.stringify(data, null, 2);

        // Se for login e sucesso, mostrar o token
        if (
            endpoint.path === '/login' &&
            response.ok &&
            data.token
        ) {
            state.response = JSON.stringify(
                {
                    ...data,
                    _info: 'Copie o token acima e cole no campo "Token de Acesso" para testar os outros endpoints.',
                },
                null,
                2,
            );
        }
    } catch (err) {
        const endTime = performance.now();
        state.responseTime = Math.round(endTime - startTime);
        state.error =
            err instanceof Error ? err.message : 'Erro desconhecido';
    } finally {
        state.isLoading = false;
    }
}

function copyToken() {
    if (selectedToken.value) {
        navigator.clipboard.writeText(selectedToken.value);
        copiedToken.value = true;
        setTimeout(() => {
            copiedToken.value = false;
        }, 2000);
    }
}

function formatJson(data: unknown): string {
    return JSON.stringify(data, null, 2);
}

const curlExample = computed(() => {
    return (endpoint: Endpoint, params: Record<string, string>) => {
        let curl = `curl -X ${endpoint.method} "${props.baseUrl}${endpoint.path}"`;
        curl += '\n  -H "Accept: application/json"';
        curl += '\n  -H "Content-Type: application/json"';

        if (endpoint.auth) {
            curl += '\n  -H "Authorization: Bearer SEU_TOKEN_AQUI"';
        }

        if (endpoint.method !== 'GET' && Object.keys(params).length > 0) {
            const body = JSON.stringify(params);
            curl += `\n  -d '${body}'`;
        }

        return curl;
    };
});
</script>

<template>
    <Head title="Documentação API" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Heading
                title="Documentação da API"
                description="Explore e teste os endpoints da API diretamente do navegador"
            />

            <!-- Token de Acesso -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Key class="h-4 w-4" />
                        Token de Acesso
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <p class="text-sm text-muted-foreground">
                            Para testar endpoints protegidos, você precisa de um
                            token de acesso. Use o endpoint de login para obter
                            um token ou cole um token existente abaixo.
                        </p>

                        <div class="flex gap-2">
                            <div class="flex-1">
                                <Input
                                    v-model="selectedToken"
                                    type="password"
                                    placeholder="Cole seu token aqui ou faça login para obter um"
                                />
                            </div>
                            <Button
                                v-if="selectedToken"
                                variant="outline"
                                size="icon"
                                @click="copyToken"
                            >
                                <Check
                                    v-if="copiedToken"
                                    class="h-4 w-4 text-green-500"
                                />
                                <Copy v-else class="h-4 w-4" />
                            </Button>
                        </div>

                        <div
                            v-if="tokens.length > 0"
                            class="text-sm text-muted-foreground"
                        >
                            <p>
                                Você tem {{ tokens.length }} token(s) ativo(s).
                                Para usar um existente, faça uma requisição de
                                login.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Base URL -->
            <Card>
                <CardContent class="py-4">
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-medium">Base URL:</span>
                        <code
                            class="rounded bg-muted px-2 py-1 font-mono text-sm"
                        >
                            {{ baseUrl }}
                        </code>
                    </div>
                </CardContent>
            </Card>

            <!-- Endpoints -->
            <div v-for="group in endpoints" :key="group.group" class="space-y-4">
                <h2 class="text-lg font-semibold">{{ group.group }}</h2>

                <Card
                    v-for="endpoint in group.endpoints"
                    :key="getEndpointKey(group.group, endpoint)"
                >
                    <Collapsible
                        :open="getState(group.group, endpoint).isOpen"
                        @update:open="toggleEndpoint(group.group, endpoint)"
                    >
                        <CollapsibleTrigger class="w-full">
                            <CardHeader
                                class="cursor-pointer transition-colors hover:bg-muted/50"
                            >
                                <div
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-4">
                                        <Badge
                                            :class="
                                                methodColors[endpoint.method] ??
                                                'bg-gray-500'
                                            "
                                            class="w-16 justify-center text-white"
                                        >
                                            {{ endpoint.method }}
                                        </Badge>
                                        <code class="font-mono text-sm">{{
                                            endpoint.path
                                        }}</code>
                                        <span class="text-muted-foreground">
                                            {{ endpoint.name }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge
                                            v-if="endpoint.auth"
                                            variant="outline"
                                            class="gap-1"
                                        >
                                            <Lock class="h-3 w-3" />
                                            Autenticado
                                        </Badge>
                                        <Badge
                                            v-else
                                            variant="secondary"
                                            class="gap-1"
                                        >
                                            <Unlock class="h-3 w-3" />
                                            Público
                                        </Badge>
                                        <ChevronDown
                                            class="h-4 w-4 transition-transform"
                                            :class="{
                                                'rotate-180': getState(
                                                    group.group,
                                                    endpoint,
                                                ).isOpen,
                                            }"
                                        />
                                    </div>
                                </div>
                            </CardHeader>
                        </CollapsibleTrigger>

                        <CollapsibleContent>
                            <CardContent class="space-y-6 border-t pt-4">
                                <!-- Descrição -->
                                <p class="text-muted-foreground">
                                    {{ endpoint.description }}
                                </p>

                                <!-- Parâmetros -->
                                <div
                                    v-if="endpoint.parameters.length > 0"
                                    class="space-y-4"
                                >
                                    <h4 class="font-semibold">Parâmetros</h4>
                                    <div class="space-y-3">
                                        <div
                                            v-for="param in endpoint.parameters"
                                            :key="param.name"
                                            class="grid gap-2"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Label :for="param.name">
                                                    {{ param.name }}
                                                </Label>
                                                <Badge
                                                    variant="outline"
                                                    class="text-xs"
                                                >
                                                    {{ param.type }}
                                                </Badge>
                                                <Badge
                                                    v-if="param.required"
                                                    variant="destructive"
                                                    class="text-xs"
                                                >
                                                    Obrigatório
                                                </Badge>
                                            </div>
                                            <Input
                                                :id="param.name"
                                                v-model="
                                                    getState(
                                                        group.group,
                                                        endpoint,
                                                    ).params[param.name]
                                                "
                                                :type="
                                                    param.name === 'password'
                                                        ? 'password'
                                                        : 'text'
                                                "
                                                :placeholder="param.description"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Botão Executar -->
                                <div class="flex items-center gap-4">
                                    <Button
                                        :disabled="
                                            getState(group.group, endpoint)
                                                .isLoading ||
                                            (endpoint.auth && !selectedToken)
                                        "
                                        @click="
                                            executeRequest(group.group, endpoint)
                                        "
                                    >
                                        <Loader2
                                            v-if="
                                                getState(group.group, endpoint)
                                                    .isLoading
                                            "
                                            class="mr-2 h-4 w-4 animate-spin"
                                        />
                                        <Play v-else class="mr-2 h-4 w-4" />
                                        Executar
                                    </Button>
                                    <span
                                        v-if="
                                            endpoint.auth && !selectedToken
                                        "
                                        class="text-sm text-muted-foreground"
                                    >
                                        Informe um token para executar este
                                        endpoint
                                    </span>
                                </div>

                                <!-- Resposta -->
                                <div
                                    v-if="
                                        getState(group.group, endpoint)
                                            .response ||
                                        getState(group.group, endpoint).error
                                    "
                                    class="space-y-2"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <h4 class="font-semibold">Resposta</h4>
                                        <div class="flex items-center gap-2">
                                            <Badge
                                                :variant="
                                                    getState(
                                                        group.group,
                                                        endpoint,
                                                    ).responseStatus &&
                                                    getState(
                                                        group.group,
                                                        endpoint,
                                                    ).responseStatus! < 400
                                                        ? 'default'
                                                        : 'destructive'
                                                "
                                            >
                                                {{
                                                    getState(
                                                        group.group,
                                                        endpoint,
                                                    ).responseStatus
                                                }}
                                            </Badge>
                                            <div
                                                class="flex items-center gap-1 text-sm text-muted-foreground"
                                            >
                                                <Clock class="h-3 w-3" />
                                                {{
                                                    getState(
                                                        group.group,
                                                        endpoint,
                                                    ).responseTime
                                                }}ms
                                            </div>
                                        </div>
                                    </div>
                                    <pre
                                        class="max-h-80 overflow-auto rounded-lg bg-muted p-4 font-mono text-sm"
                                        :class="{
                                            'border-2 border-red-500/50':
                                                getState(group.group, endpoint)
                                                    .error,
                                        }"
                                        >{{
                                            getState(group.group, endpoint)
                                                .response ||
                                            getState(group.group, endpoint)
                                                .error
                                        }}</pre
                                    >
                                </div>

                                <!-- Exemplos de Resposta -->
                                <div class="space-y-4">
                                    <h4 class="font-semibold">
                                        Exemplos de Resposta
                                    </h4>
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Check
                                                    class="h-4 w-4 text-green-500"
                                                />
                                                <span class="text-sm"
                                                    >Sucesso ({{
                                                        endpoint.response
                                                            .success.code
                                                    }})</span
                                                >
                                            </div>
                                            <pre
                                                class="max-h-40 overflow-auto rounded-lg bg-muted p-3 font-mono text-xs"
                                                >{{
                                                    formatJson(
                                                        endpoint.response
                                                            .success.example,
                                                    )
                                                }}</pre
                                            >
                                        </div>
                                        <div
                                            v-if="endpoint.response.error"
                                            class="space-y-2"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <X
                                                    class="h-4 w-4 text-red-500"
                                                />
                                                <span class="text-sm"
                                                    >Erro ({{
                                                        endpoint.response.error
                                                            .code
                                                    }})</span
                                                >
                                            </div>
                                            <pre
                                                class="max-h-40 overflow-auto rounded-lg bg-muted p-3 font-mono text-xs"
                                                >{{
                                                    formatJson(
                                                        endpoint.response.error
                                                            .example,
                                                    )
                                                }}</pre
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- cURL Example -->
                                <div class="space-y-2">
                                    <h4 class="font-semibold">Exemplo cURL</h4>
                                    <pre
                                        class="overflow-auto rounded-lg bg-muted p-3 font-mono text-xs"
                                        >{{
                                            curlExample(
                                                endpoint,
                                                getState(group.group, endpoint)
                                                    .params,
                                            )
                                        }}</pre
                                    >
                                </div>
                            </CardContent>
                        </CollapsibleContent>
                    </Collapsible>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
