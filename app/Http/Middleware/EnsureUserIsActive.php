<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            // Para requisições API, revogar o token e retornar 401
            if ($request->expectsJson()) {
                /** @var \Laravel\Sanctum\PersonalAccessToken $token */
                $token = $user->currentAccessToken();
                $token?->delete();

                return response()->json([
                    'message' => 'Sua conta está desativada.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Para requisições web, redirecionar para página de conta inativa
            if (! $request->routeIs('account.inactive', 'logout')) {
                return redirect()->route('account.inactive');
            }
        }

        return $next($request);
    }
}
