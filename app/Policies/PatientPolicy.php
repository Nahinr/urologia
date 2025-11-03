<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PatientPolicy
{
    public function viewAny(User $user): bool    { return $user->can('patient.view'); }
    public function view(User $user, Patient $p): bool { return $user->can('patient.view'); }
    public function create(User $user): bool     { return $user->can('patient.create'); }
    public function update(User $user, Patient $p): bool { return $user->can('patient.update'); }

    // Borrado lógico (SoftDelete)
    public function delete(User $user, Patient $p): bool { return $user->can('patient.delete'); }

    // Restaurar (SoftDelete)
    public function restore(User $user, Patient $p): bool { return $user->can('patient.restore'); }

    // Eliminación definitiva (opcional)
    public function forceDelete(User $user, Patient $p): bool { return $user->can('patient.forceDelete'); }
}
