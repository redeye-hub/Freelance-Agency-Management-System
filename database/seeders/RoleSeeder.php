<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['super-admin', 'project-manager', 'finance', 'hr', 'freelancer', 'client'] as $role) {
            Role::findOrCreate($role);
        }

        $users = [
            ['Admin', 'admin@fams.test', 'super-admin'],
            ['Maya Manager', 'manager@fams.test', 'project-manager'],
            ['Hana HR', 'hr@fams.test', 'hr'],
            ['Fahim Finance', 'finance@fams.test', 'finance'],
            ['Farhan Freelancer', 'freelancer@fams.test', 'freelancer'],
            ['Cara Client', 'client@fams.test', 'client'],
        ];

        foreach ($users as [$name, $email, $role]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );
            $user->syncRoles([$role]);
        }
    }
}