<script setup lang="ts">
import { index } from '@/actions/App/Http/Controllers/DashboardController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type ApiRequest,
    type ApiStats,
    type BreadcrumbItem,
    type ChartDataPoint,
    type User,
} from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import {
    Activity,
    AlertTriangle,
    CheckCircle,
    Clock,
    TrendingUp,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';

// Registrar componentes do Chart.js
ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

interface Props {
    stats: ApiStats;
    requestsPerDay: ChartDataPoint[];
    requestsByMethod: ChartDataPoint[];
    requestsByStatus: ChartDataPoint[];
    recentRequests: ApiRequest[];
    users: User[];
    canViewAllLogs: boolean;
    filterUserId: number | null;
}

const props = defineProps<Props>();

const selectedUserId = ref<string>(props.filterUserId?.toString() ?? '');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
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

const methodChartColors: Record<string, string> = {
    GET: 'rgb(59, 130, 246)',
    POST: 'rgb(34, 197, 94)',
    PUT: 'rgb(234, 179, 8)',
    PATCH: 'rgb(249, 115, 22)',
    DELETE: 'rgb(239, 68, 68)',
};

const statusChartColors: Record<string, string> = {
    success: 'rgb(34, 197, 94)',
    client_error: 'rgb(234, 179, 8)',
    server_error: 'rgb(239, 68, 68)',
    other: 'rgb(156, 163, 175)',
};

const statusLabels: Record<string, string> = {
    success: 'Sucesso (2xx)',
    client_error: 'Erro Cliente (4xx)',
    server_error: 'Erro Servidor (5xx)',
    other: 'Outros',
};

// Dados do gráfico de linha (requisições por dia)
const lineChartData = computed(() => ({
    labels: props.requestsPerDay.map((d) =>
        new Date(d.date!).toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
        }),
    ),
    datasets: [
        {
            label: 'Requisições',
            data: props.requestsPerDay.map((d) => d.count),
            borderColor: 'rgb(99, 102, 241)',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: 'rgb(99, 102, 241)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
    ],
}));

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleFont: { size: 14 },
            bodyFont: { size: 13 },
            cornerRadius: 8,
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            ticks: {
                color: 'rgb(156, 163, 175)',
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            ticks: {
                color: 'rgb(156, 163, 175)',
                stepSize: 1,
            },
        },
    },
};

// Dados do gráfico de barras (requisições por método)
const barChartData = computed(() => ({
    labels: props.requestsByMethod.map((d) => d.method),
    datasets: [
        {
            label: 'Requisições',
            data: props.requestsByMethod.map((d) => d.count),
            backgroundColor: props.requestsByMethod.map(
                (d) => methodChartColors[d.method!] ?? 'rgb(156, 163, 175)',
            ),
            borderRadius: 6,
            borderSkipped: false,
        },
    ],
}));

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            cornerRadius: 8,
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            ticks: {
                color: 'rgb(156, 163, 175)',
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            ticks: {
                color: 'rgb(156, 163, 175)',
                stepSize: 1,
            },
        },
    },
};

// Dados do gráfico de donut (status das requisições)
const doughnutChartData = computed(() => ({
    labels: props.requestsByStatus.map(
        (d) => statusLabels[d.status_group!] ?? d.status_group,
    ),
    datasets: [
        {
            data: props.requestsByStatus.map((d) => d.count),
            backgroundColor: props.requestsByStatus.map(
                (d) =>
                    statusChartColors[d.status_group!] ?? 'rgb(156, 163, 175)',
            ),
            borderWidth: 0,
            hoverOffset: 8,
        },
    ],
}));

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                padding: 16,
                usePointStyle: true,
                pointStyle: 'circle',
                color: 'rgb(156, 163, 175)',
            },
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            cornerRadius: 8,
        },
    },
};

function formatDateTime(dateStr: string) {
    return new Date(dateStr).toLocaleString('pt-BR');
}

function getStatusBadgeVariant(
    statusCode: number,
): 'default' | 'destructive' | 'secondary' | 'outline' {
    if (statusCode >= 200 && statusCode < 300) return 'default';
    if (statusCode >= 400 && statusCode < 500) return 'secondary';
    if (statusCode >= 500) return 'destructive';
    return 'outline';
}

function handleUserFilter() {
    router.get(
        index().url,
        { user_id: selectedUserId.value || undefined },
        { preserveState: true },
    );
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <Heading
                    title="Dashboard"
                    description="Monitore as requisições da API"
                />

                <!-- Filtro de usuário (apenas para admins) -->
                <div v-if="canViewAllLogs" class="flex items-center gap-2">
                    <label
                        for="user-filter"
                        class="text-sm text-muted-foreground"
                        >Filtrar por usuário:</label
                    >
                    <select
                        id="user-filter"
                        v-model="selectedUserId"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                        @change="handleUserFilter"
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
            </div>

            <!-- Cards de Estatísticas -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Total de Requisições</CardTitle
                        >
                        <Activity class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.total.toLocaleString('pt-BR') }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Sucesso</CardTitle
                        >
                        <CheckCircle class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold text-green-600 dark:text-green-400"
                        >
                            {{ stats.successful.toLocaleString('pt-BR') }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.successRate }}% das requisições
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Falhas</CardTitle
                        >
                        <AlertTriangle class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div
                            class="text-2xl font-bold text-red-600 dark:text-red-400"
                        >
                            {{ stats.failed.toLocaleString('pt-BR') }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ (100 - stats.successRate).toFixed(1) }}% das
                            requisições
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Tempo Médio de Resposta</CardTitle
                        >
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stats.avgResponseTime }}ms
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Gráficos -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Requisições por Dia -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-4 w-4" />
                            Requisições por Dia (7 dias)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="requestsPerDay.length === 0"
                            class="flex h-64 items-center justify-center text-muted-foreground"
                        >
                            Nenhum dado disponível
                        </div>
                        <div v-else class="h-64">
                            <Line
                                :data="lineChartData"
                                :options="lineChartOptions"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Requisições por Método -->
                <Card>
                    <CardHeader>
                        <CardTitle>Requisições por Método HTTP</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="requestsByMethod.length === 0"
                            class="flex h-64 items-center justify-center text-muted-foreground"
                        >
                            Nenhum dado disponível
                        </div>
                        <div v-else class="h-64">
                            <Bar
                                :data="barChartData"
                                :options="barChartOptions"
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Status e Tabela -->
            <div class="grid gap-4 md:grid-cols-3">
                <!-- Status das Requisições -->
                <Card>
                    <CardHeader>
                        <CardTitle>Status das Requisições</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="requestsByStatus.length === 0"
                            class="flex h-64 items-center justify-center text-muted-foreground"
                        >
                            Nenhum dado disponível
                        </div>
                        <div v-else class="h-64">
                            <Doughnut
                                :data="doughnutChartData"
                                :options="doughnutChartOptions"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Últimas Requisições -->
                <Card class="md:col-span-2">
                    <CardHeader>
                        <CardTitle>Últimas Requisições</CardTitle>
                    </CardHeader>
                    <CardContent class="max-h-80 overflow-y-auto">
                        <div
                            v-if="recentRequests.length === 0"
                            class="flex h-32 items-center justify-center text-muted-foreground"
                        >
                            Nenhuma requisição registrada
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="request in recentRequests"
                                :key="request.id"
                                class="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                            >
                                <div class="flex items-center gap-3">
                                    <Badge
                                        :class="
                                            methodColors[request.method] ??
                                            'bg-gray-500'
                                        "
                                        class="text-white"
                                    >
                                        {{ request.method }}
                                    </Badge>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium">{{
                                            request.path
                                        }}</span>
                                        <span
                                            v-if="
                                                canViewAllLogs && request.user
                                            "
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ request.user.name }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Badge
                                        :variant="
                                            getStatusBadgeVariant(
                                                request.status_code,
                                            )
                                        "
                                    >
                                        {{ request.status_code }}
                                    </Badge>
                                    <span class="text-xs text-muted-foreground">
                                        {{ request.response_time_ms }}ms
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ formatDateTime(request.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
