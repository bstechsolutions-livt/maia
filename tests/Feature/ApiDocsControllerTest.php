<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    /** @var User $user */
    $user = User::factory()->create();
    test()->user = $user;
});

describe('index', function () {
    it('requires authentication', function () {
        get('/api-docs')
            ->assertRedirect('/login');
    });

    it('shows api docs page for authenticated user', function () {
        actingAs(test()->user)
            ->get('/api-docs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ApiDocs')
                ->has('endpoints')
                ->has('tokens')
                ->has('user')
                ->has('baseUrl')
            );
    });

    it('returns user data correctly', function () {
        actingAs(test()->user)
            ->get('/api-docs')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('user.id', test()->user->id)
                ->where('user.name', test()->user->name)
                ->where('user.email', test()->user->email)
            );
    });

    it('returns correct base url', function () {
        actingAs(test()->user)
            ->get('/api-docs')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('baseUrl', config('app.url').'/api')
            );
    });

    it('returns existing user tokens', function () {
        // Criar alguns tokens para o usuário
        test()->user->createToken('Token 1');
        test()->user->createToken('Token 2');

        actingAs(test()->user)
            ->get('/api-docs')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('tokens', 2)
                ->has('tokens.0', fn ($token) => $token
                    ->has('id')
                    ->has('name')
                    ->has('created_at')
                    ->has('last_used_at')
                )
            );
    });

    it('returns empty tokens array for user without tokens', function () {
        actingAs(test()->user)
            ->get('/api-docs')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('tokens', 0)
            );
    });

    it('returns authentication endpoints', function () {
        actingAs(test()->user)
            ->get('/api-docs')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('endpoints', 1) // Um grupo de endpoints
                ->where('endpoints.0.group', 'Autenticação')
                ->has('endpoints.0.endpoints', 4) // login, user, logout, logout-all
            );
    });

    it('has login endpoint with correct structure', function () {
        actingAs(test()->user)
            ->get('/api-docs')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('endpoints.0.endpoints.0.name', 'Login')
                ->where('endpoints.0.endpoints.0.method', 'POST')
                ->where('endpoints.0.endpoints.0.path', '/login')
                ->where('endpoints.0.endpoints.0.auth', false)
                ->has('endpoints.0.endpoints.0.parameters', 3)
            );
    });

    it('has user endpoint with correct structure', function () {
        actingAs(test()->user)
            ->get('/api-docs')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('endpoints.0.endpoints.1.name', 'Obter Usuário Autenticado')
                ->where('endpoints.0.endpoints.1.method', 'GET')
                ->where('endpoints.0.endpoints.1.path', '/user')
                ->where('endpoints.0.endpoints.1.auth', true)
            );
    });

    it('has logout endpoints with correct structure', function () {
        actingAs(test()->user)
            ->get('/api-docs')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('endpoints.0.endpoints.2.name', 'Logout')
                ->where('endpoints.0.endpoints.2.path', '/logout')
                ->where('endpoints.0.endpoints.3.name', 'Logout de Todos os Dispositivos')
                ->where('endpoints.0.endpoints.3.path', '/logout-all')
            );
    });
});
