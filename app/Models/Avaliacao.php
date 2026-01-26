<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Avaliacao extends Model
{
    protected $table = 'avaliacoes';

    protected $fillable = [
        'descricao',
        'data_aplicacao',
        'status',
    ];

    protected $casts = [
        'data_aplicacao' => 'date',
    ];

    public function turmas()
    {
        return $this->belongsToMany(Turma::class, 'avaliacao_turma', 'id_avaliacao', 'id_turma');
    }

    public function pautas()
    {
        return $this->belongsToMany(Pauta::class, 'avaliacao_pauta', 'id_avaliacao', 'id_pauta');
    }

    public function respostas()
    {
        return $this->hasMany(Resposta::class, 'id_avaliacao');
    }
}