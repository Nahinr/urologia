<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Surgery;

class SurgeryPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('Administrator') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('surgery.view');
    }

    public function view(User $user, Surgery $model): bool
    {
        return $user->can('surgery.view');
    }

    public function create(User $user): bool
    {
        return $user->can('surgery.create') ?: true; // opcional: permitir por defecto
    }

    public function update(User $user, Surgery $model): bool
    {
        return $user->can('surgery.update') && $user->id === (int) $model->user_id;
    }

    public function delete(User $user, Surgery $model): bool
    {
        return $user->can('surgery.delete');
    }

    public function print(User $user, Surgery $model): bool
    {
        // Requisito: solo el autor puede imprimir
        return $user->id === (int) $model->user_id;
    }
}
