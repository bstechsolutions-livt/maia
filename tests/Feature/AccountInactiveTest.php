<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withHeader;

describe('inactive user web access', function () {
    it('redirects inactive user to account inactive page', function () {
        $user = User::factory()->inactive()->create();

        actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/account/inactive');
    });

    it('shows account inactive page for inactive user', function () {
        $user = User::factory()->inactive()->create();

        actingAs($user)
            ->get('/account/inactive')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('auth/AccountInactive')
            );
    });

    it('allows inactive user to logout', function () {
        $user = User::factory()->inactive()->create();

        actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');
    });

    it('allows active user to access dashboard', function () {
        /** @var User $user */
        $user = User::factory()->create(); // is_active = true by default in factory

        actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    });

    it('redirects inactive user from settings', function () {
        $user = User::factory()->inactive()->create();

        actingAs($user)
            ->get('/settings/profile')
            ->assertRedirect('/account/inactive');
    });

    it('redirects inactive user from admin routes', function () {
        $user = User::factory()->inactive()->create();

        actingAs($user)
            ->get('/admin/permissions')
            ->assertRedirect('/account/inactive');
    });

    it('redirects inactive user from reports', function () {
        $user = User::factory()->inactive()->create();

        actingAs($user)
            ->get('/reports')
            ->assertRedirect('/account/inactive');
    });

    it('redirects inactive user from api docs', function () {
        $user = User::factory()->inactive()->create();

        actingAs($user)
            ->get('/api-docs')
            ->assertRedirect('/account/inactive');
    });
});

describe('inactive user api access', function () {
    it('blocks inactive user from accessing api endpoints', function () {
        $user = User::factory()->inactive()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        getJson('/api/user', ['Authorization' => "Bearer $token"])
            ->assertUnauthorized()
            ->assertJson(['message' => 'Sua conta está desativada.']);
    });

    it('revokes token when inactive user tries to access api', function () {
        $user = User::factory()->inactive()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        getJson('/api/user', ['Authorization' => "Bearer $token"])
            ->assertUnauthorized();

        // Token should be revoked
        expect($user->tokens()->count())->toBe(0);
    });

    it('allows active user to access api', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        getJson('/api/user', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    });
});

describe('user registration creates inactive user', function () {
    it('creates user as inactive by default', function () {
        post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $user = User::where('email', 'test@example.com')->first();

        expect($user)->not->toBeNull();
        expect($user->is_active)->toBeFalse();
    });

    it('redirects new user to inactive page after registration', function () {
        post('/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        get('/dashboard')
            ->assertRedirect('/account/inactive');
    });
});
