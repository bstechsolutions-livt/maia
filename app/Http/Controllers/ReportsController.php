<?php

namespace App\Http\Controllers;

use App\Models\ApiRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $canViewAllLogs = $user->hasPermission('admin.logs.view');

        // Filtros
        $filterUserId = $canViewAllLogs ? $request->input('user_id') : $user->id;
        $filterMethod = $request->input('method');
        $filterStatus = $request->input('status');
        $filterDateFrom = $request->input('date_from');
        $filterDateTo = $request->input('date_to');
        $filterPath = $request->input('path');

        // Base query
        $query = ApiRequest::query()->with('user:id,name,email');

        // Aplicar filtro de usuário
        if ($filterUserId) {
            $query->where('user_id', $filterUserId);
        } elseif (! $canViewAllLogs) {
            $query->where('user_id', $user->id);
        }

        // Filtro por método HTTP
        if ($filterMethod) {
            $query->where('method', $filterMethod);
        }

        // Filtro por status
        if ($filterStatus === 'success') {
            $query->whereBetween('status_code', [200, 299]);
        } elseif ($filterStatus === 'client_error') {
            $query->whereBetween('status_code', [400, 499]);
        } elseif ($filterStatus === 'server_error') {
            $query->where('status_code', '>=', 500);
        }

        // Filtro por período
        if ($filterDateFrom) {
            $query->whereDate('created_at', '>=', $filterDateFrom);
        }
        if ($filterDateTo) {
            $query->whereDate('created_at', '<=', $filterDateTo);
        }

        // Filtro por path
        if ($filterPath) {
            $query->where('path', 'like', "%{$filterPath}%");
        }

        // Paginação
        $requests = $query->latest()->paginate(20)->withQueryString();

        // Lista de usuários para filtro (apenas para admins)
        $users = $canViewAllLogs
            ? User::select('id', 'name', 'email')->orderBy('name')->get()
            : collect();

        // Métodos disponíveis para filtro
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        return Inertia::render('Reports', [
            'requests' => $requests,
            'users' => $users,
            'methods' => $methods,
            'canViewAllLogs' => $canViewAllLogs,
            'filters' => [
                'user_id' => $filterUserId,
                'method' => $filterMethod,
                'status' => $filterStatus,
                'date_from' => $filterDateFrom,
                'date_to' => $filterDateTo,
                'path' => $filterPath,
            ],
        ]);
    }
}
