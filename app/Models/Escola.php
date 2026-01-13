<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Escola extends Model
{
    use HasFactory;
    use Notifiable;

    protected $table = 'escolas';

    protected $fillable = [
        'codigo',
        'nome',
        'telefone',
        'email',
    ];

    public function casts(): array
    {
        return [
            'codigo' => 'string',
            'nome' => 'string',
            'telefone' => 'string',
            'email' => 'string',
        ];
    }

    public function usuarios()
    {
        return $this->hasMany(User::class, 'id_escola');
    }

    public function turmas()
    {
        return $this->hasMany(Turma::class, 'id_escola');
    }
    
    public function professores()
    {
        return $this->hasMany(Professor::class, 'id_escola');
    }
}
