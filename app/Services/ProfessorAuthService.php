<?php
// app/Services/ProfessorAuthService.php

namespace App\Services;

use App\Models\Professor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class ProfessorAuthService
{
    private const ROLE_PROFESSOR = 'Professor';

    public function tentarAutenticarProfessor(SocialiteUser $oauthUser): ?User
    {
        $email = strtolower(trim($oauthUser->getEmail()));
        $professores = Professor::buscarTodosPorEmail($email);

        if ($professores->isEmpty()) {
            return null;
        }

        // Verifica se já existe User vinculado a algum desses professores
        $professorComConta = $professores->firstWhere('user_id', '!=', null);

        if ($professorComConta) {
            // Garante que todos os professores com esse email estão vinculados
            $this->vincularProfessoresAoUser($professores, $professorComConta->user);
            return $this->atualizarTokens($professorComConta->user, $oauthUser);
        }

        return $this->criarUsuarioParaProfessores($professores, $oauthUser);
    }

    private function criarUsuarioParaProfessores($professores, SocialiteUser $oauthUser): User
    {
        return DB::transaction(function () use ($professores, $oauthUser) {
            $primeiro = $professores->first();

            $user = User::create([
                'name' => $oauthUser->getName() ?? $primeiro->nome,
                'email' => $oauthUser->getEmail(),
                'password' => bcrypt(Str::random(32)),
                'email_approved' => true,
                'email_verified_at' => now(),
                'id_escola' => $primeiro->id_escola, // Usa a primeira escola como padrão
                'google_id' => $oauthUser->getId(),
                'google_email' => $oauthUser->getEmail(),
                'google_token' => $oauthUser->token,
                'google_refresh_token' => $oauthUser->refreshToken,
                'google_token_expires_in' => now()->addSeconds(
                    max(60, (int) ($oauthUser->expiresIn ?? 3600) - 60)
                ),
            ]);

            $this->vincularProfessoresAoUser($professores, $user);

            $user->assignRole(self::ROLE_PROFESSOR);

            return $user;
        });
    }

    /**
     * Vincula todos os professores ao User
     */
    private function vincularProfessoresAoUser($professores, User $user): void
    {
        foreach ($professores as $professor) {
            if ($professor->user_id !== $user->id) {
                $professor->update(['user_id' => $user->id]);
            }
        }
    }

    private function atualizarTokens(User $user, SocialiteUser $oauthUser): User
    {
        $user->forceFill([
            'google_token' => $oauthUser->token,
            'google_refresh_token' => $oauthUser->refreshToken ?? $user->google_refresh_token,
            'google_token_expires_in' => now()->addSeconds(
                max(60, (int) ($oauthUser->expiresIn ?? 3600) - 60)
            ),
        ])->save();

        return $user;
    }
}