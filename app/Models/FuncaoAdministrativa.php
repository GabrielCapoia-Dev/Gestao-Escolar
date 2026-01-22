<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuncaoAdministrativa extends Model
{
    //
    protected $table = 'funcao_administrativa';

    public $fillable = [
        'nome',
        'portaria'
    ];

    public function casts()
    {
        return [
            'nome' => 'string',
            'portaria' => 'string',
        ];
    }

    public function professores()
    {
        return $this->hasMany(Professor::class, 'funcao_administrativa_id');
    }
}
