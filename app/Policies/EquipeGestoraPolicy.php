<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EquipeGestora as Model;

class EquipeGestoraPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Listar Equipe Gestora');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $model): bool
    {
        return $user->hasPermissionTo('Listar Equipe Gestora');

    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar Equipe Gestora');
        ;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $model): bool
    {
        return $user->hasPermissionTo('Editar Equipe Gestora');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->hasPermissionTo('Excluir Equipe Gestora');
    }

    // /**
    //  * Determine whether the user can restore the model.
    //  */
    // public function restore(User $user, Model $model): bool
    // {
    //     return false;
    // }

    // /**
    //  * Determine whether the user can permanently delete the model.
    //  */
    // public function forceDelete(User $user, Model $model): bool
    // {
    //     return false;
    // }
}
