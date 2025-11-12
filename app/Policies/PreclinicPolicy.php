<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Preclinic;

class PreclinicPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('Administrator') ? true : null;
    }

    public function viewAny(User $user): bool   { return $user->can('preclinic.view'); }
    public function view(User $user, Preclinic $r): bool { return $user->can('preclinic.view'); }
    public function create(User $user): bool    { return $user->can('preclinic.create'); }
    public function update(User $user, Preclinic $r): bool
    {
        return $user->can('preclinic.update') && $user->id === (int) $r->user_id;
    }
    public function delete(User $user, Preclinic $r): bool { return $user->can('preclinic.delete'); }
}
