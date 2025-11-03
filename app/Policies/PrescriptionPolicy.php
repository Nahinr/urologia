<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Prescription;

class PrescriptionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Si es Admin, puede todo
        return $user->hasRole('Administrator') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('prescription.view');
    }

    public function view(User $user, Prescription $rx): bool
    {
        return $user->can('prescription.view');
    }

    public function create(User $user): bool
    {
        return $user->can('prescription.create');
    }

    public function update(User $user, Prescription $rx): bool
    {
        // Debe tener permiso y ser autor
        return $user->can('prescription.update') && $user->id === (int) $rx->user_id;
    }

    public function delete(User $user, Prescription $rx): bool
    {
        // Solo Admin (o quien tenga prescription.delete, que en tu seeder es Admin)
        return $user->can('prescription.delete');
    }

    public function print (User $user, Prescription $rx): bool
    {
         return $user->id === (int) $rx->user_id;
    }
}
