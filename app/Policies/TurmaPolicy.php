<?php

namespace App\Policies;

use App\Models\Turma;
use App\Models\User;

class TurmaPolicy
{
    /**
     * Ver qualquer turma.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Listar Turmas');
        // ou $user->can('Listar Turmas');
    }

    /**
     * Ver uma turma específica.
     */
    public function view(User $user, Turma $turma): bool
    {
        return $user->hasPermissionTo('Listar Turmas');
    }

    /**
     * Criar turma.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar Turmas');
    }

    /**
     * Editar turma.
     */
    public function update(User $user, Turma $turma): bool
    {
        return $user->hasPermissionTo('Editar Turmas');
    }

    /**
     * Excluir turma.
     */
    public function delete(User $user, Turma $turma): bool
    {
        return $user->hasPermissionTo('Excluir Turmas');
    }


    // /**
    //  * Determine whether the user can restore the model.
    //  */
    // public function restore(User $user, User $model): bool
    // {
    //     return false;
    // }

    // /**
    //  * Determine whether the user can permanently delete the model.
    //  */
    // public function forceDelete(User $user, User $model): bool
    // {
    //     return false;
    // }
}
