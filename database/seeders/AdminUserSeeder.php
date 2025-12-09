<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'bstech.solutions@outlook.com'],
            [
                'name' => 'BSTech Solutions',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Atribui todas as permissões ao usuário admin
        $allPermissions = Permission::all();
        $user->permissions()->syncWithoutDetaching($allPermissions->pluck('id'));
    }
}
