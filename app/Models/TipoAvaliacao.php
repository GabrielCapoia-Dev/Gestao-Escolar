<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoAvaliacao extends Model
{
    protected $table = 'tipo_avaliacoes';

    protected $fillable = [
        'tipo_avaliacao',
    ];

    public function alternativas()
    {
        return $this->hasMany(Alternativa::class, 'id_tipo_avaliacao');
    }

    public function pautas()
    {
        return $this->hasMany(Pauta::class, 'id_tipo_avaliacao');
    }
}