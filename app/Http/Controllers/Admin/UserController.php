<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    /**
     * Ativa ou desativa um usuário.
     */
    public function toggleActive(User $user): RedirectResponse
    {
        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $status = $user->is_active ? 'ativado' : 'desativado';

        return redirect()
            ->back()
            ->with('success', "Usuário {$status} com sucesso!");
    }
}
