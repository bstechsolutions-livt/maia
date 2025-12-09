<?php

use App\Models\User;

use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

describe('web login', function () {
    it('allows active users to login', function () {
        /** @var User $user */
        $user = User::factory()->withoutTwoFactor()->create([
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    });

    it('blocks inactive users from logging in', function () {
        /** @var User $user */
        $user = User::factory()->withoutTwoFactor()->inactive()->create([
            'password' => bcrypt('password'),
        ]);

        post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    });
});

describe('api login', function () {
    it('allows active users to get a token', function () {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertSuccessful()
            ->assertJsonStructure(['token', 'user']);
    });

    it('blocks inactive users from getting a token', function () {
        /** @var User $user */
        $user = User::factory()->inactive()->create([
            'password' => bcrypt('password'),
        ]);

        postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('returns correct error message for inactive users', function () {
        /** @var User $user */
        $user = User::factory()->inactive()->create([
            'password' => bcrypt('password'),
        ]);

        postJson(route('api.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertJsonFragment(['email' => ['Sua conta está desativada.']]);
    });
});
