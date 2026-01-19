<?php

namespace App\Services;

use App\Models\Role;

class RoleService
{

    public function adminRole($record): bool
    {
        $roles = [
            'Admin',
        ];

        foreach ($roles as $role) {
            if ($record->name == $role) {
                return true;
            }
        }
        return false;
    }

    public function bloquearCampo($record, $context): bool
    {

        if ($context == 'create') {
            return false;
        }

        $roles = [
            'Admin',
            'Secretario',
        ];

        foreach ($roles as $role) {
            if ($record->name == $role) {
                return true;
            }
        }

        return false;
    }
    public function bloquearExclusao($record): bool
    {
        
        $roles = [
            'Admin',
            'Secretario',
        ];

        foreach ($roles as $role) {
            if ($record->name == $role) {
                return true;
            }
        }

        return false;
    }

    public function bloquearSelecaoBulkActions($record): bool
    {
        $bloqueados = ['Admin', 'Secretario'];
        return !in_array($record->name, $bloqueados);
    }
    
}   