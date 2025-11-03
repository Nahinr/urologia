<?php

namespace App\Policies;

use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RolePolicy
{
    public function viewAny(User $user): bool        { return $user->can('role.view'); }
    public function view(User $user, Role $role): bool{ return $user->can('role.view'); }
    public function create(User $user): bool         { return $user->can('role.create'); }
    public function update(User $user, Role $role): bool{ return $user->can('role.update'); }
    public function delete(User $user, Role $role): bool{ return $user->can('role.delete'); }

    // Si no usas soft deletes en roles:
    public function restore(User $user, Role $role): bool     { return false; }
    public function forceDelete(User $user, Role $role): bool { return false; }
}
