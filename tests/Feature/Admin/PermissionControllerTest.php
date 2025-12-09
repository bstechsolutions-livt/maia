<?php

use App\Models\Permission;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

/** @var User $user */
$user = null;

beforeEach(function () use (&$user) {
    // Criar permissões de administração
    $viewPermission = Permission::factory()->create(['slug' => 'admin.permissions.view', 'name' => 'Visualizar Permissões']);
    $managePermission = Permission::factory()->create(['slug' => 'admin.permissions.manage', 'name' => 'Gerenciar Permissões']);

    $user = User::factory()->create();
    $user->permissions()->attach([$viewPermission->id, $managePermission->id]);
    test()->user = $user;
});

describe('index', function () {
    it('redirects guests to login', function () {
        get(route('admin.permissions.index'))
            ->assertRedirect(route('login'));
    });

    it('returns forbidden for users without permission', function () {
        /** @var User $userWithoutPermission */
        $userWithoutPermission = User::factory()->create();

        actingAs($userWithoutPermission)
            ->get(route('admin.permissions.index'))
            ->assertForbidden();
    });

    it('shows permissions list for authenticated users', function () {
        // Já temos 2 permissões criadas no beforeEach + 3 novas = 5
        Permission::factory()->count(3)->create();

        actingAs(test()->user)
            ->get(route('admin.permissions.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Permissions/Index')
                ->has('permissions', 5)
                ->has('groupedPermissions')
            );
    });

    it('groups permissions correctly', function () {
        // Limpar permissões anteriores e criar novas com grupos específicos
        Permission::query()->delete();

        // Recriar permissões do admin para manter o acesso
        $viewPermission = Permission::factory()->withGroup('administracao')->create([
            'slug' => 'admin.permissions.view',
        ]);
        $managePermission = Permission::factory()->withGroup('administracao')->create([
            'slug' => 'admin.permissions.manage',
        ]);
        test()->user->permissions()->sync([$viewPermission->id, $managePermission->id]);

        // Criar permissões de outros grupos
        Permission::factory()->withGroup('usuarios')->count(2)->create();
        Permission::factory()->withGroup('financeiro')->count(3)->create();

        actingAs(test()->user)
            ->get(route('admin.permissions.index'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->has('groupedPermissions.administracao', 2)
                ->has('groupedPermissions.usuarios', 2)
                ->has('groupedPermissions.financeiro', 3)
            );
    });
});

describe('create', function () {
    it('shows create form for authenticated users', function () {
        actingAs(test()->user)
            ->get(route('admin.permissions.create'))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->component('Admin/Permissions/Create'));
    });
});

describe('store', function () {
    it('creates a new permission', function () {
        $data = [
            'name' => 'Gerenciar Usuários',
            'slug' => 'usuarios.gerenciar',
            'description' => 'Permite gerenciar usuários',
            'group' => 'usuarios',
        ];

        actingAs(test()->user)
            ->post(route('admin.permissions.store'), $data)
            ->assertRedirect(route('admin.permissions.index'));

        assertDatabaseHas('permissions', $data);
    });

    it('validates required fields', function () {
        actingAs(test()->user)
            ->post(route('admin.permissions.store'), [])
            ->assertSessionHasErrors(['name', 'slug', 'group']);
    });

    it('validates unique name', function () {
        Permission::factory()->create(['name' => 'Existing Permission']);

        actingAs(test()->user)
            ->post(route('admin.permissions.store'), [
                'name' => 'Existing Permission',
                'slug' => 'new.slug',
                'group' => 'geral',
            ])
            ->assertSessionHasErrors(['name']);
    });

    it('validates unique slug', function () {
        Permission::factory()->create(['slug' => 'existing.slug']);

        actingAs(test()->user)
            ->post(route('admin.permissions.store'), [
                'name' => 'New Permission',
                'slug' => 'existing.slug',
                'group' => 'geral',
            ])
            ->assertSessionHasErrors(['slug']);
    });

    it('validates slug format', function () {
        actingAs(test()->user)
            ->post(route('admin.permissions.store'), [
                'name' => 'Test Permission',
                'slug' => 'Invalid Slug With Spaces',
                'group' => 'geral',
            ])
            ->assertSessionHasErrors(['slug']);
    });
});

describe('edit', function () {
    it('shows edit form for existing permission', function () {
        $permission = Permission::factory()->create();

        actingAs(test()->user)
            ->get(route('admin.permissions.edit', $permission))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Permissions/Edit')
                ->has('permission')
                ->where('permission.id', $permission->id)
            );
    });
});

describe('update', function () {
    it('updates an existing permission', function () {
        $permission = Permission::factory()->create();

        $data = [
            'name' => 'Updated Name',
            'slug' => 'updated.slug',
            'description' => 'Updated description',
            'group' => 'updated-group',
        ];

        actingAs(test()->user)
            ->put(route('admin.permissions.update', $permission), $data)
            ->assertRedirect(route('admin.permissions.index'));

        assertDatabaseHas('permissions', [
            'id' => $permission->id,
            ...$data,
        ]);
    });

    it('allows updating with same name/slug as current', function () {
        $permission = Permission::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original.slug',
        ]);

        actingAs(test()->user)
            ->put(route('admin.permissions.update', $permission), [
                'name' => 'Original Name',
                'slug' => 'original.slug',
                'group' => 'geral',
            ])
            ->assertRedirect(route('admin.permissions.index'));
    });

    it('prevents duplicate name with other permission', function () {
        Permission::factory()->create(['name' => 'Other Permission']);
        $permission = Permission::factory()->create();

        actingAs(test()->user)
            ->put(route('admin.permissions.update', $permission), [
                'name' => 'Other Permission',
                'slug' => $permission->slug,
                'group' => $permission->group,
            ])
            ->assertSessionHasErrors(['name']);
    });
});

describe('destroy', function () {
    it('deletes an existing permission', function () {
        $permission = Permission::factory()->create();

        actingAs(test()->user)
            ->delete(route('admin.permissions.destroy', $permission))
            ->assertRedirect(route('admin.permissions.index'));

        assertDatabaseMissing('permissions', ['id' => $permission->id]);
    });

    it('removes permission from users when deleted', function () {
        $permission = Permission::factory()->create();
        /** @var User $user */
        $user = User::factory()->create();
        $user->permissions()->attach($permission);

        actingAs(test()->user)
            ->delete(route('admin.permissions.destroy', $permission));

        expect($user->fresh()->permissions)->toHaveCount(0);
    });
});
