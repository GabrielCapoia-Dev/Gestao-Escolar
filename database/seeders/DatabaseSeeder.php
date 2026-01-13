<?php

namespace Database\Seeders;

use App\Models\DominioEmail;
use App\Models\Escola;
use App\Models\Professor;
use App\Models\Serie;
use App\Models\Turma;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa cache das permissões do Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Lista de permissões que serão atribuídas à role Admin
        $permissionsList = [
            'Listar Usuários',
            'Criar Usuários',
            'Editar Usuários',
            'Excluir Usuários',
            'Listar Níveis de Acesso',
            'Criar Níveis de Acesso',
            'Editar Níveis de Acesso',
            'Excluir Níveis de Acesso',
            'Listar Permissões de Execução',
            'Criar Permissões de Execução',
            'Editar Permissões de Execução',
            'Excluir Permissões de Execução',
            'Listar Dominios de Email',
            'Criar Dominios de Email',
            'Editar Dominios de Email',
            'Excluir Dominios de Email',
            'Listar Séries',
            'Criar Séries',
            'Editar Séries',
            'Excluir Séries',
            'Listar Escolas',
            'Criar Escolas',
            'Editar Escolas',
            'Excluir Escolas',
            'Listar Turmas',
            'Criar Turmas',
            'Editar Turmas',
            'Excluir Turmas',
            'Listar Professores',
            'Criar Professores',
            'Editar Professores',
            'Excluir Professores',
        ];

        $secretarioPermissionsList = [
            'Listar Usuários',
            'Criar Usuários',
            'Editar Usuários',
            'Excluir Usuários',
            'Listar Turmas',
            'Criar Turmas',
            'Editar Turmas',
            'Excluir Turmas',
            'Listar Professores',
            'Criar Professores',
            'Editar Professores',
            'Excluir Professores',
        ];

        $password = "Senha@123";

        // Criação das permissões
        foreach ($permissionsList as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Criação da rule Admin
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $secretarioRole = Role::firstOrCreate(['name' => 'Secretario']);
        $usuarioRole = Role::firstOrCreate(['name' => 'Usuario']);

        // Atribui todas as permissões à role Admin
        $adminRole->syncPermissions($permissionsList);
        $secretarioRole->syncPermissions($secretarioPermissionsList);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'email_approved' => true
            ]
        );

        $secretarioUser = User::firstOrCreate(
            ['email' => 'secretario@secretario.com'],
            [
                'name' => 'Secretario',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'email_approved' => true
            ]
        );

        $adminUser->assignRole($adminRole);
        $secretarioUser->assignRole($secretarioRole);

        /**
         * Criar domínios de email
         */

        $emailPermissionsList = [
            [
                'gmail.com',
                'edu.umuarama.pr.gov.br',
                'umuarama.pr.gov.br',
            ],
            [
                'Geral',
                'Educação',
                'Administrativo'
            ]
        ];

        foreach ($emailPermissionsList[0] as $index => $dominio) {
            $setor = $emailPermissionsList[1][$index] ?? 'Geral';

            DominioEmail::create([
                'dominio_email' => $dominio,
                'setor' => $setor,
                'status' => 1,
            ]);
        }

        $this->call([]);

        // 1. Criar Escolas (10 escolas)
        $this->command->info('Criando escolas...');
        $escolas = Escola::factory(10)->create();

        // 2. Criar Séries (17 séries - da educação infantil ao ensino médio)
        $this->command->info('Criando séries...');
        $series = Serie::factory(17)->create();

        // 3. Criar Turmas (50 turmas distribuídas entre escolas e séries)
        $this->command->info('Criando turmas...');
        $turmas = Turma::factory(50)->create();

        // 4. Criar Professores (30 professores)
        $this->command->info('Criando professores...');
        $professores = Professor::factory(30)->create();

        // 5. Associar Professores às Turmas (cada professor leciona em 2-5 turmas)
        $this->command->info('Associando professores às turmas...');
        foreach ($professores as $professor) {
            $quantidadeTurmas = rand(2, 5);
            $turmasAleatorias = Turma::inRandomOrder()->limit($quantidadeTurmas)->pluck('id');
            $professor->turmas()->attach($turmasAleatorias);
        }
        $this->command->info('✅ Dados populados com sucesso!');
        $this->command->info("📊 Resumo:");
        $this->command->info("   - Escolas: " . Escola::count());
        $this->command->info("   - Séries: " . Serie::count());
        $this->command->info("   - Turmas: " . Turma::count());
        $this->command->info("   - Professores: " . Professor::count());
        $this->command->info("   - Relacionamentos Professor-Turma: " . DB::table('professor_turma')->count());
    }
}
