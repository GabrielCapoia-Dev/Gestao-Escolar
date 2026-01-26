<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pauta extends Model
{
    protected $table = 'pautas';

    protected $fillable = [
        'id_tipo_avaliacao',
        'id_componente_curricular',
        'id_serie',
        'pauta',
    ];

    public function tipoAvaliacao()
    {
        return $this->belongsTo(TipoAvaliacao::class, 'id_tipo_avaliacao');
    }

    public function componenteCurricular()
    {
        return $this->belongsTo(ComponenteCurricular::class, 'id_componente_curricular');
    }

    public function serie()
    {
        return $this->belongsTo(Serie::class, 'id_serie');
    }

    public function avaliacoes()
    {
        return $this->belongsToMany(Avaliacao::class, 'avaliacao_pauta', 'id_pauta', 'id_avaliacao');
    }

    public function alternativas()
    {
        return $this->hasManyThrough(
            Alternativa::class,
            TipoAvaliacao::class,
            'id',
            'id_tipo_avaliacao',
            'id_tipo_avaliacao',
            'id'
        );
    }
}