<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resposta extends Model
{
    protected $table = 'respostas';

    protected $fillable = [
        'id_avaliacao',
        'id_aluno',
        'id_pauta',
        'id_alternativa',
        'observacao',
        'id_professor',
    ];

    public function avaliacao()
    {
        return $this->belongsTo(Avaliacao::class, 'id_avaliacao');
    }

    public function aluno()
    {
        return $this->belongsTo(Aluno::class, 'id_aluno');
    }

    public function pauta()
    {
        return $this->belongsTo(Pauta::class, 'id_pauta');
    }

    public function alternativa()
    {
        return $this->belongsTo(Alternativa::class, 'id_alternativa');
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'id_professor');
    }
}