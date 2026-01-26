<?php
// app/Models/Aluno.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Aluno extends Model
{
    protected $table = 'alunos';

    protected $fillable = [
        'id_turma',
        'cgm',
        'nome',
        'data_nascimento',
        'idade',
        'sexo',
        'situacao',
        'data_matricula',
        'ativo',
        'registro_anterior_id',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'data_matricula' => 'date',
        'ativo' => 'boolean',
    ];

    // ==================== RELAÇÕES ====================

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class, 'id_turma');
    }

    public function registroAnterior(): BelongsTo
    {
        return $this->belongsTo(Aluno::class, 'registro_anterior_id');
    }

    public function historicoPosteriores(): HasMany
    {
        return $this->hasMany(Aluno::class, 'registro_anterior_id');
    }

    // ==================== SCOPES ====================

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopePorCgm(Builder $query, string $cgm): Builder
    {
        return $query->where('cgm', $cgm);
    }

    // ==================== MÉTODOS ====================

    /**
     * Retorna todo o histórico do aluno (por CGM)
     */
    public function getHistoricoCompleto()
    {
        return static::where('cgm', $this->cgm)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Retorna a escola atual do aluno
     */
    public function getEscolaIdAttribute(): ?int
    {
        return $this->turma?->id_escola;
    }

    /**
     * Desativa este registro (quando criar um novo)
     */
    public function desativar(): void
    {
        $this->update(['ativo' => false]);
    }
}