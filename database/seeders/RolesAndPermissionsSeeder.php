<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'facturas.ver',
            'facturas.crear',
            'facturas.editar',
            'facturas.confirmar',
            'facturas.anular',
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
            'empresas.ver',
            'empresas.editar',
            'empresas.crear',
        ];

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso);
        }

        $facturador = Role::findOrCreate('facturador');
        $facturador->syncPermissions([
            'facturas.ver',
            'facturas.crear',
            'facturas.editar',
            'facturas.confirmar',
            'facturas.anular',
        ]);

        $contador = Role::findOrCreate('contador');
        $contador->syncPermissions([
            'facturas.ver',
        ]);

        
        
        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions([
            'facturas.ver',
            'facturas.crear',
            'facturas.editar',
            'facturas.confirmar',
            'facturas.anular',
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
            'empresas.ver',
            'empresas.editar',
        ]);

        
        
        $superAdmin = Role::findOrCreate('super_admin');
        $superAdmin->syncPermissions([
            ...$permisos,
        ]);
    }
}
