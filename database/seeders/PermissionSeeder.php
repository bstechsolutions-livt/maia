<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Administração
            [
                'name' => 'Gerenciar Permissões',
                'slug' => 'admin.permissions.manage',
                'description' => 'Permite criar, editar e excluir permissões',
                'group' => 'administracao',
            ],
            [
                'name' => 'Visualizar Permissões',
                'slug' => 'admin.permissions.view',
                'description' => 'Permite visualizar permissões',
                'group' => 'administracao',
            ],
            [
                'name' => 'Gerenciar Usuários',
                'slug' => 'admin.users.manage',
                'description' => 'Permite gerenciar permissões de usuários',
                'group' => 'administracao',
            ],
            [
                'name' => 'Visualizar Usuários',
                'slug' => 'admin.users.view',
                'description' => 'Permite visualizar usuários',
                'group' => 'administracao',
            ],
        ];

        foreach ($permissions as $data) {
            Permission::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
