<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserPermissionUpdateRequest;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserPermissionController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function edit(User $user): Response
    {
        $user->load('permissions');

        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        $groupedPermissions = $permissions->groupBy('group');

        return Inertia::render('Admin/Users/EditPermissions', [
            'user' => $user,
            'permissions' => $permissions,
            'groupedPermissions' => $groupedPermissions,
            'userPermissionIds' => $user->permissions->pluck('id')->toArray(),
        ]);
    }

    public function update(UserPermissionUpdateRequest $request, User $user): RedirectResponse
    {
        $user->syncPermissions($request->validated('permissions') ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Permissões do usuário atualizadas com sucesso!');
    }
}
