<?php

use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

describe('login', function () {
    it('returns a token for valid credentials', function () {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ])
            ->assertSuccessful()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email'],
            ]);
    });

    it('accepts a custom device name', function () {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'iPhone 15 Pro',
        ])
            ->assertSuccessful()
            ->assertJsonStructure(['token', 'user']);

        expect($user->tokens()->first()->name)->toBe('iPhone 15 Pro');
    });

    it('returns validation error for invalid credentials', function () {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('returns validation error for missing fields', function () {
        postJson(route('api.login'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('returns validation error for non-existent user', function () {
        postJson(route('api.login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('logout', function () {
    it('revokes the current token', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        postJson(route('api.logout'), [], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertSuccessful()
            ->assertJson(['message' => 'Logout realizado com sucesso.']);

        expect($user->tokens()->count())->toBe(0);
    });

    it('returns unauthorized for unauthenticated users', function () {
        postJson(route('api.logout'))
            ->assertUnauthorized();
    });
});

describe('logout-all', function () {
    it('revokes all tokens', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $user->createToken('device-1');
        $user->createToken('device-2');
        $user->createToken('device-3');
        $token = $user->createToken('current-device')->plainTextToken;

        expect($user->tokens()->count())->toBe(4);

        postJson(route('api.logout-all'), [], [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertSuccessful()
            ->assertJson(['message' => 'Todos os dispositivos foram desconectados.']);

        expect($user->fresh()->tokens()->count())->toBe(0);
    });
});

describe('user', function () {
    it('returns the authenticated user', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $response = getJson(route('api.user'), [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertSuccessful()
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
            ]);

        expect($response->json('user.id'))->toBe($user->id);
    });

    it('returns unauthorized for unauthenticated users', function () {
        getJson(route('api.user'))
            ->assertUnauthorized();
    });
});
