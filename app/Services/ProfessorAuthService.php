<?php
// app/Services/ProfessorAuthService.php

namespace App\Services;

use App\Models\Professor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Spatie\Permission\Models\Role;

class ProfessorAuthService
{
    /**
     * Tenta autenticar um professor pelo email do Google.
     * 
     * @param SocialiteUser $oauthUser Dados do usuário Google
     * @return User|null Retorna User se for professor, null caso contrário
     */
    public function tentarAutenticarProfessor(SocialiteUser $oauthUser): ?User
    {
        $email = strtolower(trim($oauthUser->getEmail()));
        
        $professor = Professor::buscarPorEmail($email);
        
        if (!$professor) {
            return null; // Não é professor, fluxo normal continua
        }

        // Professor já tem conta? Retorna o User existente
        if ($professor->temContaUsuario()) {
            return $this->atualizarTokensGoogle($professor->user, $oauthUser);
        }

        // Professor sem conta: cria User e vincula
        return $this->criarUsuarioParaProfessor($professor, $oauthUser);
    }

    /**
     * Cria um novo User para o Professor e vincula.
     */
    private function criarUsuarioParaProfessor(Professor $professor, SocialiteUser $oauthUser): User
    {
        return DB::transaction(function () use ($professor, $oauthUser) {
            // Cria o User
            $user = User::create([
                'name' => $oauthUser->getName() ?? $professor->nome,
                'email' => $oauthUser->getEmail(),
                'password' => bcrypt(Str::random(32)), // Senha aleatória (login só por Google)
                'email_approved' => true, // Professor já é pré-aprovado
                'email_verified_at' => now(),
                'id_escola' => $professor->id_escola,
                'google_id' => $oauthUser->getId(),
                'google_email' => $oauthUser->getEmail(),
                'google_token' => encrypt($oauthUser->token), // Criptografado!
                'google_refresh_token' => $oauthUser->refreshToken 
                    ? encrypt($oauthUser->refreshToken) 
                    : null,
                'google_token_expires_in' => now()->addSeconds(
                    max(60, (int) ($oauthUser->expiresIn ?? 3600) - 60)
                ),
            ]);

            // Vincula Professor ao User
            $professor->update(['user_id' => $user->id]);

            // Atribui role "Professor"
            $this->atribuirRoleProfessor($user);

            return $user;
        });
    }

    /**
     * Atribui a role Professor ao usuário.
     */
    private function atribuirRoleProfessor(User $user): void
    {
        $role = Role::firstOrCreate(['name' => 'Professor']);
        
        if (!$user->hasRole('Professor')) {
            $user->assignRole($role);
        }
    }

    /**
     * Atualiza tokens Google de um professor existente.
     */
    private function atualizarTokensGoogle(User $user, SocialiteUser $oauthUser): User
    {
        $user->forceFill([
            'google_token' => encrypt($oauthUser->token),
            'google_refresh_token' => $oauthUser->refreshToken 
                ? encrypt($oauthUser->refreshToken) 
                : $user->google_refresh_token,
            'google_token_expires_in' => now()->addSeconds(
                max(60, (int) ($oauthUser->expiresIn ?? 3600) - 60)
            ),
        ])->save();

        return $user;
    }

    /**
     * Verifica se um email pertence a um professor cadastrado.
     */
    public function emailEhDeProfessor(string $email): bool
    {
        return Professor::buscarPorEmail($email) !== null;
    }
}