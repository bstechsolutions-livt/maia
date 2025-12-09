<?php

use App\Models\Permission;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    /** @var User $admin */
    $admin = User::factory()->create();
    test()->admin = $admin;

    // Criar permissões necessárias para acessar as rotas de admin
    $viewPermission = Permission::factory()->create([
        'name' => 'Ver Usuários',
        'slug' => 'admin.users.view',
        'group' => 'administracao',
    ]);
    $managePermission = Permission::factory()->create([
        'name' => 'Gerenciar Usuários',
        'slug' => 'admin.users.manage',
        'group' => 'administracao',
    ]);

    $admin->permissions()->attach([$viewPermission->id, $managePermission->id]);
});

describe('toggleActive', function () {
    it('redirects guests to login', function () {
        /** @var User $user */
        $user = User::factory()->create();

        patch(route('admin.users.toggle-active', $user))
            ->assertRedirect(route('login'));
    });

    it('returns forbidden for users without permission', function () {
        /** @var User $userWithoutPermission */
        $userWithoutPermission = User::factory()->create();
        /** @var User $targetUser */
        $targetUser = User::factory()->create();

        actingAs($userWithoutPermission)
            ->patch(route('admin.users.toggle-active', $targetUser))
            ->assertForbidden();
    });

    it('can deactivate a user', function () {
        /** @var User $user */
        $user = User::factory()->create(['is_active' => true]);

        actingAs(test()->admin)
            ->patch(route('admin.users.toggle-active', $user))
            ->assertRedirect();

        expect($user->fresh()->is_active)->toBeFalse();
    });

    it('can activate a user', function () {
        /** @var User $user */
        $user = User::factory()->inactive()->create();

        actingAs(test()->admin)
            ->patch(route('admin.users.toggle-active', $user))
            ->assertRedirect();

        expect($user->fresh()->is_active)->toBeTrue();
    });

    it('shows success message when toggling user status', function () {
        /** @var User $user */
        $user = User::factory()->create(['is_active' => true]);

        actingAs(test()->admin)
            ->patch(route('admin.users.toggle-active', $user))
            ->assertSessionHas('success');
    });
});
