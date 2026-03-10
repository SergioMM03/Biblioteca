<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'books.viewAny',
            'books.view',
            'books.create',
            'books.update',
            'books.delete',
            'loans.viewHistory',
            'loans.create',
            'loans.return',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $bibliotecario = Role::findOrCreate('bibliotecario', 'web');
        $docente = Role::findOrCreate('docente', 'web');
        $estudiante = Role::findOrCreate('estudiante', 'web');

        $bibliotecario->syncPermissions([
            'books.viewAny',
            'books.view',
            'books.create',
            'books.update',
            'books.delete',
            'loans.viewHistory',
        ]);

        $docente->syncPermissions([
            'books.viewAny',
            'books.view',
            'loans.viewHistory',
            'loans.create',
            'loans.return',
        ]);

        $estudiante->syncPermissions([
            'books.viewAny',
            'books.view',
            'loans.viewHistory',
            'loans.create',
            'loans.return',
        ]);
    }
}
