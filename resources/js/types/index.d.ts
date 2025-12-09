import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
    permissions: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    permissions?: Permission[];
}

export interface Permission {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    group: string;
    created_at: string;
    updated_at: string;
}

export interface ApiRequest {
    id: number;
    user_id: number | null;
    method: string;
    path: string;
    full_url: string;
    route_name: string | null;
    ip_address: string | null;
    user_agent: string | null;
    headers: Record<string, unknown> | null;
    query_params: Record<string, unknown> | null;
    request_body: Record<string, unknown> | null;
    status_code: number;
    response_body: Record<string, unknown> | null;
    response_time_ms: number;
    device_name: string | null;
    token_id: number | null;
    created_at: string;
    updated_at: string;
    user?: User;
}

export interface ApiStats {
    total: number;
    successful: number;
    failed: number;
    avgResponseTime: number;
    successRate: number;
}

export interface ChartDataPoint {
    date?: string;
    method?: string;
    status_group?: string;
    count: number;
}

export type BreadcrumbItemType = BreadcrumbItem;
