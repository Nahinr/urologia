<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $perms = [
            'user.view','user.create','user.update','user.delete',
            'role.view','role.create','role.update','role.delete',
            'permission.view',
            'patient.view','patient.create','patient.update','patient.delete',
            'patient.restore','patient.forceDelete',
            'patient.attachments.viewAny','patient.attachments.view','patient.attachments.create',
            'patient.attachments.update','patient.attachments.delete','patient.attachments.download',
            'appointment.view','appointment.create','appointment.update','appointment.delete',
            'appointment.restore','appointment.forceDelete',
            'history.view','history.create','history.update','history.delete',
            'history.restore','history.forceDelete',
            'clinical-background.view','clinical-background.create','clinical-background.update',
            'clinical-background.delete','clinical-background.restore','clinical-background.forceDelete',
            'prescription.view','prescription.create','prescription.update',
            'prescription.delete','prescription.restore','prescription.forceDelete',
            'preclinic.view','preclinic.create','preclinic.update','preclinic.delete',
            'preclinic.restore','preclinic.forceDelete',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $admin  = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $doctor = Role::firstOrCreate(['name' => 'Doctor', 'guard_name' => 'web']);
        $recept = Role::firstOrCreate(['name' => 'Receptionist', 'guard_name' => 'web']);

        $admin->syncPermissions(Permission::all());

        $doctor->syncPermissions([
            'patient.view','patient.update','patient.create',
            'appointment.view','appointment.create','appointment.update',
            'history.view','history.create','history.update',
            'user.view','user.create','user.delete',
            'patient.attachments.viewAny','patient.attachments.view','patient.attachments.create',
            'patient.attachments.update','patient.attachments.download',
            'clinical-background.view','clinical-background.create','clinical-background.update',
            'prescription.view','prescription.create','prescription.update',
            'preclinic.view','preclinic.create','preclinic.update',
        ]);

        $recept->syncPermissions([
            'patient.view','patient.create','patient.update',
            'appointment.view','appointment.create','appointment.update',
        ]);

        $adminEmail = 'admin@admin.com';
        if ($u = User::where('email', $adminEmail)->first()) {
            $u->syncRoles(['Administrator']);
        }

        $hthEmail = 'hthadmin@admin.com';
        $hthUser = User::firstOrCreate(
            ['email' => $hthEmail],
            [
                'name' => 'HTH Admin',
                'password' => Hash::make('password123'),
            ]
        );

        if (!$hthUser->hasRole('Administrator')) {
            $hthUser->assignRole('Administrator');
        }
    }
}
