<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool       { return $user->can('user.view'); }
    public function view(User $user, User $record): bool { return $user->can('user.view'); }
    public function create(User $user): bool        { return $user->can('user.create'); }
    public function update(User $user, User $record): bool { return $user->can('user.update'); }

    // No usamos delete/restore/forceDelete porque NO hay soft delete ni borrado real
    // La activación/inactivación se considera una "actualización":
    public function toggleStatus(User $user, User $record): bool { return $user->can('user.update'); }
}
