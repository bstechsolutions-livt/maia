<?php

namespace App\Http\Controllers;

use App\Models\ApiRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $canViewAllLogs = $user->hasPermission('admin.logs.view');

        // Filtro de usuário (apenas para admins)
        $filterUserId = $canViewAllLogs ? $request->input('user_id') : $user->id;

        // Base query
        $query = ApiRequest::query();

        if ($filterUserId) {
            $query->where('user_id', $filterUserId);
        } elseif (! $canViewAllLogs) {
            $query->where('user_id', $user->id);
        }

        // Estatísticas gerais
        $stats = $this->getStats($query->clone());

        // Requisições por dia (últimos 7 dias)
        $requestsPerDay = $this->getRequestsPerDay($query->clone());

        // Requisições por método
        $requestsByMethod = $this->getRequestsByMethod($query->clone());

        // Requisições por status
        $requestsByStatus = $this->getRequestsByStatus($query->clone());

        // Últimas requisições
        $recentRequests = $query->clone()
            ->with('user:id,name,email')
            ->latest()
            ->take(10)
            ->get();

        // Lista de usuários para filtro (apenas para admins)
        $users = $canViewAllLogs
            ? User::select('id', 'name', 'email')->orderBy('name')->get()
            : collect();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'requestsPerDay' => $requestsPerDay,
            'requestsByMethod' => $requestsByMethod,
            'requestsByStatus' => $requestsByStatus,
            'recentRequests' => $recentRequests,
            'users' => $users,
            'canViewAllLogs' => $canViewAllLogs,
            'filterUserId' => $filterUserId,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ApiRequest>  $query
     * @return array<string, mixed>
     */
    private function getStats($query): array
    {
        $total = $query->clone()->count();
        $successful = $query->clone()->whereBetween('status_code', [200, 299])->count();
        $failed = $query->clone()->where('status_code', '>=', 400)->count();
        $avgResponseTime = (int) $query->clone()->avg('response_time_ms');

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'avgResponseTime' => $avgResponseTime,
            'successRate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0,
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ApiRequest>  $query
     * @return array<int, array<string, mixed>>
     */
    private function getRequestsPerDay($query): array
    {
        return $query
            ->where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ApiRequest>  $query
     * @return array<int, array<string, mixed>>
     */
    private function getRequestsByMethod($query): array
    {
        return $query
            ->select('method', DB::raw('COUNT(*) as count'))
            ->groupBy('method')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ApiRequest>  $query
     * @return array<int, array<string, mixed>>
     */
    private function getRequestsByStatus($query): array
    {
        return $query
            ->select(
                DB::raw("CASE 
                    WHEN status_code >= 200 AND status_code < 300 THEN 'success'
                    WHEN status_code >= 400 AND status_code < 500 THEN 'client_error'
                    WHEN status_code >= 500 THEN 'server_error'
                    ELSE 'other'
                END as status_group"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('status_group')
            ->get()
            ->toArray();
    }
}
