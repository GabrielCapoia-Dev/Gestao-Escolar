<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CriarPermissoesEquipeGestora extends Command
{
    protected $signature = 'permissoes:equipe-gestora';

    protected $description = 'Cria permissões da Equipe Gestora e vincula à role Admin';

    public function handle(): int
    {
        // Limpa cache de permissões
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissoes = [
            'Listar Equipe Gestora',
            'Criar Equipe Gestora',
            'Editar Equipe Gestora',
            'Excluir Equipe Gestora',
        ];

        $this->info('Criando permissões da Equipe Gestora...');

        foreach ($permissoes as $nome) {
            Permission::firstOrCreate(['name' => $nome]);
            $this->line("✔ Permissão: {$nome}");
        }

        $adminRole = Role::where('name', 'Admin')->first();

        if (!$adminRole) {
            $this->error('Role Admin não encontrada.');
            return Command::FAILURE;
        }

        $adminRole->givePermissionTo($permissoes);

        $this->info('Permissões vinculadas à role Admin com sucesso ✅');

        return Command::SUCCESS;
    }
}
