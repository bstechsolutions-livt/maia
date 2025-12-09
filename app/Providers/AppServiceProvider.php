<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configurar autorização do Log Viewer
        LogViewer::auth(function ($request) {
            // Em ambiente local, permitir acesso
            if (app()->environment('local')) {
                return true;
            }

            // Em produção, verificar se usuário está autenticado
            if (! $request->user()) {
                return false;
            }

            // Permitir acesso apenas por e-mail específico
            $allowedEmails = array_filter(explode(',', env('LOG_VIEWER_ALLOWED_EMAILS', '')));

            return in_array($request->user()->email, $allowedEmails);
        });
    }
}
