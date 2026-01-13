<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Professor extends Model
{
    protected $table = 'professores';

    protected $fillable = [
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

    public function turmas()
    {
        return $this->belongsToMany(Turma::class);
    }
}
