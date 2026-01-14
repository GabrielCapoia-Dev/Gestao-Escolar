<?php

namespace App\Policies;

use App\Models\ComponenteCurricular;
use App\Models\User;

class ComponenteCurricularPolicy

{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Listar Componentes Curriculares');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ComponenteCurricular $model): bool
    {
        return $user->hasPermissionTo('Listar Componentes Curriculares');

    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar Componentes Curriculares');
        ;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ComponenteCurricular $model): bool
    {
        return $user->hasPermissionTo('Editar Componentes Curriculares');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ComponenteCurricular $model): bool
    {
        return $user->hasPermissionTo('Excluir Componentes Curriculares');
    }

    // /**
    //  * Determine whether the user can restore the model.
    //  */
    // public function restore(User $user, ComponenteCurricular $model): bool
    // {
    //     return false;
    // }

    // /**
    //  * Determine whether the user can permanently delete the model.
    //  */
    // public function forceDelete(User $user, ComponenteCurricular $model): bool
    // {
    //     return false;
    // }
}
