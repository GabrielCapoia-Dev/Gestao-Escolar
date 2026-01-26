<?php
// app/Observers/ProfessorObserver.php

namespace App\Observers;

use App\Models\Professor;

class ProfessorObserver
{
    public function updating(Professor $professor): void
    {
        // Se o email mudou, remove o vínculo com usuário
        if ($professor->isDirty('email') && $professor->user_id) {
            $professor->user_id = null;
        }
    }
}