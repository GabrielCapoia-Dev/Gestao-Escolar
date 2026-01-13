<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Professor extends Model
{
    use HasFactory;

    protected $table = 'professores';

    protected $fillable = [
        'id_escola',
        'matricula',
        'nome',
        'email',
        'telefone',
    ];
    public function casts(): array
    {
        return [
            'matricula' => 'string',
            'nome' => 'string',
            'email' => 'string',
            'telefone' => 'string',
        ];
    }

    public function escola()
    {
        return $this->belongsTo(Escola::class, 'id_escola');
    }

    public function turmas()
    {
        return $this->belongsToMany(Turma::class);
    }

    public function componentesPorTurma()
    {
        return $this->belongsToMany(
            ComponenteCurricular::class,
            'turma_componente_professor'
        )->withPivot('turma_id');
    }
}
