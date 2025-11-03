<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MedicalHistory;

class MedicalHistoryPolicy
{
    /**
     * Admin: acceso total.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Si usas permisos en vez de rol, puedes chequear $user->hasRole('Administrator')
        return $user->hasRole('Administrator') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('history.view');
    }

    public function view(User $user, MedicalHistory $history): bool
    {
        return $user->can('history.view');
    }

    public function create(User $user): bool
    {
        return $user->can('history.create');
    }

    public function update(User $user, MedicalHistory $history): bool
    {
        // 1) Debe tener permiso de actualizar
        // 2) Debe ser el autor de la consulta
        return $user->can('history.update') && $user->id === (int) $history->user_id;
    }

    public function delete(User $user, MedicalHistory $history): bool
    {
        // Solo Administrator (ya cubierto por before) o bien permiso history.delete (que solo tiene Admin)
        return $user->can('history.delete');
    }
}
