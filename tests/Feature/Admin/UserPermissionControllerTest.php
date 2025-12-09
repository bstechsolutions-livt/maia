<?php

use App\Models\Permission;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

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

describe('index', function () {
    it('redirects guests to login', function () {
        get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    });

    it('returns forbidden for users without permission', function () {
        /** @var User $userWithoutPermission */
        $userWithoutPermission = User::factory()->create();

        actingAs($userWithoutPermission)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    });

    it('shows users list for authenticated users', function () {
        User::factory()->count(3)->create();

        actingAs(test()->admin)
            ->get(route('admin.users.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/Index')
                ->has('users', 4) // 3 created + 1 admin
            );
    });

    it('loads users with their permissions', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $permissions = Permission::factory()->count(2)->create();
        $user->permissions()->attach($permissions);

        actingAs(test()->admin)
            ->get(route('admin.users.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->has('users.0.permissions')
            );
    });
});

describe('edit', function () {
    it('shows edit permissions form for a user', function () {
        /** @var User $user */
        $user = User::factory()->create();
        Permission::factory()->count(3)->create();

        actingAs(test()->admin)
            ->get(route('admin.users.permissions.edit', $user))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/EditPermissions')
                ->has('user')
                ->where('user.id', $user->id)
                ->has('permissions', 5) // 3 created + 2 from beforeEach
                ->has('groupedPermissions')
                ->has('userPermissionIds')
            );
    });

    it('returns current user permissions ids', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $permissions = Permission::factory()->count(3)->create();
        $user->permissions()->attach($permissions->take(2));

        actingAs(test()->admin)
            ->get(route('admin.users.permissions.edit', $user))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->has('userPermissionIds', 2)
            );
    });
});

describe('update', function () {
    it('updates user permissions', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $permissions = Permission::factory()->count(3)->create();

        actingAs(test()->admin)
            ->put(route('admin.users.permissions.update', $user), [
                'permissions' => $permissions->pluck('id')->toArray(),
            ])
            ->assertRedirect(route('admin.users.index'));

        expect($user->fresh()->permissions)->toHaveCount(3);
    });

    it('removes all permissions when empty array is sent', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $permissions = Permission::factory()->count(3)->create();
        $user->permissions()->attach($permissions);

        actingAs(test()->admin)
            ->put(route('admin.users.permissions.update', $user), [
                'permissions' => [],
            ])
            ->assertRedirect(route('admin.users.index'));

        expect($user->fresh()->permissions)->toHaveCount(0);
    });

    it('removes all permissions when null is sent', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $permissions = Permission::factory()->count(2)->create();
        $user->permissions()->attach($permissions);

        actingAs(test()->admin)
            ->put(route('admin.users.permissions.update', $user), [])
            ->assertRedirect(route('admin.users.index'));

        expect($user->fresh()->permissions)->toHaveCount(0);
    });

    it('syncs permissions correctly', function () {
        /** @var User $user */
        $user = User::factory()->create();
        $oldPermissions = Permission::factory()->count(2)->create();
        $newPermissions = Permission::factory()->count(2)->create();
        $user->permissions()->attach($oldPermissions);

        actingAs(test()->admin)
            ->put(route('admin.users.permissions.update', $user), [
                'permissions' => $newPermissions->pluck('id')->toArray(),
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        expect($user->permissions)->toHaveCount(2);
        expect($user->permissions->pluck('id')->toArray())
            ->toBe($newPermissions->pluck('id')->toArray());
    });

    it('validates permissions exist', function () {
        /** @var User $user */
        $user = User::factory()->create();

        actingAs(test()->admin)
            ->put(route('admin.users.permissions.update', $user), [
                'permissions' => [999, 1000],
            ])
            ->assertSessionHasErrors(['permissions.0', 'permissions.1']);
    });
});
