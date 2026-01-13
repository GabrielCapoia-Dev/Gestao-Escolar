<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Professor;

class ProfessorPolicy

{
    /**
     * Ver qualquer professor.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Listar Professores');
    }

    /**
     * Ver um professor específico.
     */
    public function view(User $user, Professor $professor): bool
    {
        return $user->hasPermissionTo('Listar Professores');
    }

    /**
     * Criar professor.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar Professores');
    }

    /**
     * Atualizar professor.
     */
    public function update(User $user, Professor $professor): bool
    {
        return $user->hasPermissionTo('Editar Professores');
    }

    /**
     * Deletar professor.
     */
    public function delete(User $user, Professor $professor): bool
    {
        return $user->hasPermissionTo('Excluir Professores');
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
