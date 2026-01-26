<?php

namespace App\Http\Controllers;

use App\Models\Resposta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvaliacaoController extends Controller
{
    public function salvarResposta(Request $request)
    {
        $validated = $request->validate([
            'id_avaliacao' => 'required|exists:avaliacoes,id',
            'id_turma' => 'required|exists:turmas,id',
            'id_aluno' => 'required|exists:alunos,id',
            'id_pauta' => 'required|exists:pautas,id',
            'id_alternativa' => 'required|exists:alternativas,id',
            'observacao' => 'nullable|string|max:1000',
        ]);

        $professor = Auth::user()->professor ?? null;
        $professorId = $professor?->id ?? Auth::id();

        Resposta::updateOrCreate(
            [
                'id_avaliacao' => $request->id_avaliacao,
                'id_aluno' => $request->id_aluno,
                'id_pauta' => $request->id_pauta,
            ],
            [
                'id_alternativa' => $request->id_alternativa,
                'observacao' => $request->observacao,
                'id_professor' => auth()->user()->professor->id ?? null,
            ]
        );


        return response()->json(['success' => true]);
    }
}
