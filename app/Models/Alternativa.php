<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alternativa extends Model
{
    protected $table = 'alternativas';

    protected $fillable = [
        'id_tipo_avaliacao',
        'texto',
        'tem_observacao',
    ];

    protected $casts = [
        'tem_observacao' => 'boolean',
    ];

    public function tipoAvaliacao()
    {
        return $this->belongsTo(TipoAvaliacao::class, 'id_tipo_avaliacao');
    }

    public function respostas()
    {
        return $this->hasMany(Resposta::class, 'id_alternativa');
    }
}