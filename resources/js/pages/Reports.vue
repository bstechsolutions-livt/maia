<script setup lang="ts">
import { index } from '@/actions/App/Http/Controllers/ReportsController';
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
import { type ApiRequest, type BreadcrumbItem, type User } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Clock,
    FileText,
    Filter,
    Globe,
    Monitor,
    Search,
    User as UserIcon,
    X,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface Filters {
    user_id: number | null;
    method: string | null;
    status: string | null;
    date_from: string | null;
    date_to: string | null;
    path: string | null;
}

interface Props {
    requests: PaginatedData<ApiRequest>;
    users: User[];
    methods: string[];
    canViewAllLogs: boolean;
    filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Relatórios',
        href: index().url,
    },
];

// Estado dos filtros
const filterUserId = ref<string>(props.filters.user_id?.toString() ?? '');
const filterMethod = ref<string>(props.filters.method ?? '');
const filterStatus = ref<string>(props.filters.status ?? '');
const filterDateFrom = ref<string>(props.filters.date_from ?? '');
const filterDateTo = ref<string>(props.filters.date_to ?? '');
const filterPath = ref<string>(props.filters.path ?? '');
const showFilters = ref(false);

// Estado das linhas expandidas
const expandedRows = ref<Set<number>>(new Set());

const methodColors: Record<string, string> = {
    GET: 'bg-blue-500',
    POST: 'bg-green-500',
    PUT: 'bg-yellow-500',
    PATCH: 'bg-orange-500',
    DELETE: 'bg-red-500',
};

const statusLabels: Record<string, string> = {
    success: 'Sucesso (2xx)',
    client_error: 'Erro Cliente (4xx)',
    server_error: 'Erro Servidor (5xx)',
};

function getStatusBadgeVariant(
    statusCode: number,
): 'default' | 'destructive' | 'secondary' | 'outline' {
    if (statusCode >= 200 && statusCode < 300) return 'default';
    if (statusCode >= 400 && statusCode < 500) return 'secondary';
    if (statusCode >= 500) return 'destructive';
    return 'outline';
}

function formatDateTime(dateStr: string) {
    return new Date(dateStr).toLocaleString('pt-BR');
}

function formatDate(dateStr: string) {
    return new Date(dateStr).toLocaleDateString('pt-BR');
}

function toggleRow(id: number) {
    if (expandedRows.value.has(id)) {
        expandedRows.value.delete(id);
    } else {
        expandedRows.value.add(id);
    }
}

function applyFilters() {
    router.get(
        index().url,
        {
            user_id: filterUserId.value || undefined,
            method: filterMethod.value || undefined,
            status: filterStatus.value || undefined,
            date_from: filterDateFrom.value || undefined,
            date_to: filterDateTo.value || undefined,
            path: filterPath.value || undefined,
        },
        { preserveState: true },
    );
}

function clearFilters() {
    filterUserId.value = '';
    filterMethod.value = '';
    filterStatus.value = '';
    filterDateFrom.value = '';
    filterDateTo.value = '';
    filterPath.value = '';
    router.get(index().url, {}, { preserveState: true });
}

function hasActiveFilters(): boolean {
    return !!(
        filterUserId.value ||
        filterMethod.value ||
        filterStatus.value ||
        filterDateFrom.value ||
        filterDateTo.value ||
        filterPath.value
    );
}

function formatJson(data: Record<string, unknown> | null): string {
    if (!data || Object.keys(data).length === 0) return '-';
    return JSON.stringify(data, null, 2);
}

// Watch para aplicar filtros automaticamente ao mudar selects
watch([filterMethod, filterStatus], () => {
    applyFilters();
});
</script>

<template>
    <Head title="Relatórios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <Heading
                    title="Relatórios de API"
                    description="Visualize e filtre todas as requisições da API"
                />

                <Button
                    variant="outline"
                    size="sm"
                    @click="showFilters = !showFilters"
                >
                    <Filter class="mr-2 h-4 w-4" />
                    Filtros
                    <Badge
                        v-if="hasActiveFilters()"
                        variant="secondary"
                        class="ml-2"
                    >
                        Ativos
                    </Badge>
                </Button>
            </div>

            <!-- Filtros -->
            <Card v-if="showFilters">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-base">Filtros</CardTitle>
                        <Button
                            v-if="hasActiveFilters()"
                            variant="ghost"
                            size="sm"
                            @click="clearFilters"
                        >
                            <X class="mr-2 h-4 w-4" />
                            Limpar
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <!-- Filtro por Usuário -->
                        <div v-if="canViewAllLogs" class="space-y-2">
                            <Label for="filter-user">Usuário</Label>
                            <select
                                id="filter-user"
                                v-model="filterUserId"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                                @change="applyFilters"
                            >
                                <option value="">Todos os usuários</option>
                                <option
                                    v-for="user in users"
                                    :key="user.id"
                                    :value="user.id.toString()"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Filtro por Método -->
                        <div class="space-y-2">
                            <Label for="filter-method">Método HTTP</Label>
                            <select
                                id="filter-method"
                                v-model="filterMethod"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                            >
                                <option value="">Todos</option>
                                <option
                                    v-for="method in methods"
                                    :key="method"
                                    :value="method"
                                >
                                    {{ method }}
                                </option>
                            </select>
                        </div>

                        <!-- Filtro por Status -->
                        <div class="space-y-2">
                            <Label for="filter-status">Status</Label>
                            <select
                                id="filter-status"
                                v-model="filterStatus"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                            >
                                <option value="">Todos</option>
                                <option value="success">Sucesso (2xx)</option>
                                <option value="client_error">
                                    Erro Cliente (4xx)
                                </option>
                                <option value="server_error">
                                    Erro Servidor (5xx)
                                </option>
                            </select>
                        </div>

                        <!-- Filtro por Data Inicial -->
                        <div class="space-y-2">
                            <Label for="filter-date-from">Data Inicial</Label>
                            <Input
                                id="filter-date-from"
                                v-model="filterDateFrom"
                                type="date"
                                @change="applyFilters"
                            />
                        </div>

                        <!-- Filtro por Data Final -->
                        <div class="space-y-2">
                            <Label for="filter-date-to">Data Final</Label>
                            <Input
                                id="filter-date-to"
                                v-model="filterDateTo"
                                type="date"
                                @change="applyFilters"
                            />
                        </div>

                        <!-- Filtro por Path -->
                        <div class="space-y-2">
                            <Label for="filter-path">Path</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="filter-path"
                                    v-model="filterPath"
                                    placeholder="/api/..."
                                    @keyup.enter="applyFilters"
                                />
                                <Button
                                    variant="secondary"
                                    size="icon"
                                    @click="applyFilters"
                                >
                                    <Search class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Resumo -->
            <div
                class="flex items-center justify-between text-sm text-muted-foreground"
            >
                <span>
                    Mostrando {{ requests.data.length }} de
                    {{ requests.total }} requisições
                </span>
                <span v-if="requests.total > 0">
                    Página {{ requests.current_page }} de
                    {{ requests.last_page }}
                </span>
            </div>

            <!-- Lista de Requisições -->
            <Card>
                <CardContent class="p-0">
                    <div
                        v-if="requests.data.length === 0"
                        class="flex h-64 items-center justify-center text-muted-foreground"
                    >
                        <div class="text-center">
                            <FileText class="mx-auto h-12 w-12 opacity-50" />
                            <p class="mt-4">Nenhuma requisição encontrada</p>
                        </div>
                    </div>

                    <div v-else class="divide-y">
                        <Collapsible
                            v-for="request in requests.data"
                            :key="request.id"
                            :open="expandedRows.has(request.id)"
                            @update:open="toggleRow(request.id)"
                        >
                            <CollapsibleTrigger class="w-full">
                                <div
                                    class="flex cursor-pointer items-center justify-between p-4 transition-colors hover:bg-muted/50"
                                >
                                    <div class="flex items-center gap-4">
                                        <ChevronDown
                                            class="h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200"
                                            :class="{
                                                'rotate-180': expandedRows.has(
                                                    request.id,
                                                ),
                                            }"
                                        />
                                        <Badge
                                            :class="
                                                methodColors[request.method] ??
                                                'bg-gray-500'
                                            "
                                            class="w-16 justify-center text-white"
                                        >
                                            {{ request.method }}
                                        </Badge>
                                        <div class="flex flex-col items-start">
                                            <span class="font-medium">{{
                                                request.path
                                            }}</span>
                                            <span
                                                v-if="
                                                    canViewAllLogs &&
                                                    request.user
                                                "
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ request.user.name }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <Badge
                                            :variant="
                                                getStatusBadgeVariant(
                                                    request.status_code,
                                                )
                                            "
                                        >
                                            {{ request.status_code }}
                                        </Badge>
                                        <span
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ request.response_time_ms }}ms
                                        </span>
                                        <span
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{
                                                formatDateTime(
                                                    request.created_at,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </CollapsibleTrigger>

                            <CollapsibleContent>
                                <div class="border-t bg-muted/30 p-4">
                                    <div class="grid gap-6 md:grid-cols-2">
                                        <!-- Informações Gerais -->
                                        <div class="space-y-4">
                                            <h4
                                                class="flex items-center gap-2 font-semibold"
                                            >
                                                <Globe class="h-4 w-4" />
                                                Informações da Requisição
                                            </h4>
                                            <div
                                                class="grid grid-cols-2 gap-3 text-sm"
                                            >
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >ID:</span
                                                    >
                                                    <span class="ml-2 font-mono"
                                                        >#{{ request.id }}</span
                                                    >
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Método:</span
                                                    >
                                                    <span class="ml-2">{{
                                                        request.method
                                                    }}</span>
                                                </div>
                                                <div class="col-span-2">
                                                    <span
                                                        class="text-muted-foreground"
                                                        >URL Completa:</span
                                                    >
                                                    <span
                                                        class="ml-2 font-mono text-xs break-all"
                                                        >{{
                                                            request.full_url
                                                        }}</span
                                                    >
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Rota:</span
                                                    >
                                                    <span
                                                        class="ml-2 font-mono text-xs"
                                                        >{{
                                                            request.route_name ??
                                                            '-'
                                                        }}</span
                                                    >
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Status:</span
                                                    >
                                                    <Badge
                                                        class="ml-2"
                                                        :variant="
                                                            getStatusBadgeVariant(
                                                                request.status_code,
                                                            )
                                                        "
                                                    >
                                                        {{
                                                            request.status_code
                                                        }}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Informações do Cliente -->
                                        <div class="space-y-4">
                                            <h4
                                                class="flex items-center gap-2 font-semibold"
                                            >
                                                <Monitor class="h-4 w-4" />
                                                Informações do Cliente
                                            </h4>
                                            <div
                                                class="grid grid-cols-2 gap-3 text-sm"
                                            >
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >IP:</span
                                                    >
                                                    <span
                                                        class="ml-2 font-mono"
                                                        >{{
                                                            request.ip_address ??
                                                            '-'
                                                        }}</span
                                                    >
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Dispositivo:</span
                                                    >
                                                    <span class="ml-2">{{
                                                        request.device_name ??
                                                        '-'
                                                    }}</span>
                                                </div>
                                                <div
                                                    v-if="
                                                        canViewAllLogs &&
                                                        request.user
                                                    "
                                                >
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Usuário:</span
                                                    >
                                                    <span class="ml-2">{{
                                                        request.user.name
                                                    }}</span>
                                                </div>
                                                <div v-if="request.token_id">
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Token ID:</span
                                                    >
                                                    <span class="ml-2 font-mono"
                                                        >#{{
                                                            request.token_id
                                                        }}</span
                                                    >
                                                </div>
                                                <div class="col-span-2">
                                                    <span
                                                        class="text-muted-foreground"
                                                        >User Agent:</span
                                                    >
                                                    <span
                                                        class="ml-2 text-xs break-all"
                                                        >{{
                                                            request.user_agent ??
                                                            '-'
                                                        }}</span
                                                    >
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tempo de Resposta -->
                                        <div class="space-y-4">
                                            <h4
                                                class="flex items-center gap-2 font-semibold"
                                            >
                                                <Clock class="h-4 w-4" />
                                                Performance
                                            </h4>
                                            <div
                                                class="grid grid-cols-2 gap-3 text-sm"
                                            >
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Tempo de
                                                        Resposta:</span
                                                    >
                                                    <span
                                                        class="ml-2 font-semibold"
                                                        :class="{
                                                            'text-green-600':
                                                                request.response_time_ms <
                                                                100,
                                                            'text-yellow-600':
                                                                request.response_time_ms >=
                                                                    100 &&
                                                                request.response_time_ms <
                                                                    500,
                                                            'text-red-600':
                                                                request.response_time_ms >=
                                                                500,
                                                        }"
                                                        >{{
                                                            request.response_time_ms
                                                        }}ms</span
                                                    >
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Data/Hora:</span
                                                    >
                                                    <span class="ml-2">{{
                                                        formatDateTime(
                                                            request.created_at,
                                                        )
                                                    }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Usuário -->
                                        <div
                                            v-if="
                                                canViewAllLogs && request.user
                                            "
                                            class="space-y-4"
                                        >
                                            <h4
                                                class="flex items-center gap-2 font-semibold"
                                            >
                                                <UserIcon class="h-4 w-4" />
                                                Usuário
                                            </h4>
                                            <div
                                                class="grid grid-cols-2 gap-3 text-sm"
                                            >
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Nome:</span
                                                    >
                                                    <span class="ml-2">{{
                                                        request.user.name
                                                    }}</span>
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Email:</span
                                                    >
                                                    <span class="ml-2">{{
                                                        request.user.email
                                                    }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Query Params -->
                                        <div
                                            v-if="
                                                request.query_params &&
                                                Object.keys(
                                                    request.query_params,
                                                ).length > 0
                                            "
                                            class="col-span-2 space-y-2"
                                        >
                                            <h4 class="font-semibold">
                                                Query Parameters
                                            </h4>
                                            <pre
                                                class="max-h-40 overflow-auto rounded-lg bg-muted p-3 font-mono text-xs"
                                                >{{
                                                    formatJson(
                                                        request.query_params,
                                                    )
                                                }}</pre
                                            >
                                        </div>

                                        <!-- Request Body -->
                                        <div
                                            v-if="
                                                request.request_body &&
                                                Object.keys(
                                                    request.request_body,
                                                ).length > 0
                                            "
                                            class="col-span-2 space-y-2"
                                        >
                                            <h4 class="font-semibold">
                                                Request Body
                                            </h4>
                                            <pre
                                                class="max-h-40 overflow-auto rounded-lg bg-muted p-3 font-mono text-xs"
                                                >{{
                                                    formatJson(
                                                        request.request_body,
                                                    )
                                                }}</pre
                                            >
                                        </div>

                                        <!-- Response Body -->
                                        <div
                                            v-if="
                                                request.response_body &&
                                                Object.keys(
                                                    request.response_body,
                                                ).length > 0
                                            "
                                            class="col-span-2 space-y-2"
                                        >
                                            <h4 class="font-semibold">
                                                Response Body
                                            </h4>
                                            <pre
                                                class="max-h-40 overflow-auto rounded-lg bg-muted p-3 font-mono text-xs"
                                                >{{
                                                    formatJson(
                                                        request.response_body,
                                                    )
                                                }}</pre
                                            >
                                        </div>

                                        <!-- Headers -->
                                        <div
                                            v-if="
                                                request.headers &&
                                                Object.keys(request.headers)
                                                    .length > 0
                                            "
                                            class="col-span-2 space-y-2"
                                        >
                                            <h4 class="font-semibold">
                                                Headers
                                            </h4>
                                            <pre
                                                class="max-h-40 overflow-auto rounded-lg bg-muted p-3 font-mono text-xs"
                                                >{{
                                                    formatJson(request.headers)
                                                }}</pre
                                            >
                                        </div>
                                    </div>
                                </div>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </CardContent>
            </Card>

            <!-- Paginação -->
            <div
                v-if="requests.last_page > 1"
                class="flex items-center justify-center gap-2"
            >
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!requests.prev_page_url"
                    @click="
                        requests.prev_page_url &&
                        router.get(requests.prev_page_url)
                    "
                >
                    <ChevronLeft class="h-4 w-4" />
                    Anterior
                </Button>

                <div class="flex items-center gap-1">
                    <template v-for="link in requests.links" :key="link.label">
                        <Button
                            v-if="
                                link.url &&
                                !link.label.includes('Previous') &&
                                !link.label.includes('Next')
                            "
                            variant="outline"
                            size="sm"
                            :class="{
                                'bg-primary text-primary-foreground':
                                    link.active,
                            }"
                            @click="link.url && router.get(link.url)"
                        >
                            {{ link.label }}
                        </Button>
                    </template>
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!requests.next_page_url"
                    @click="
                        requests.next_page_url &&
                        router.get(requests.next_page_url)
                    "
                >
                    Próximo
                    <ChevronRight class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
