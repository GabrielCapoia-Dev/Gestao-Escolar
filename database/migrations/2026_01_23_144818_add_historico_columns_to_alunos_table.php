<?php
// database/migrations/2026_01_23_000002_adjust_alunos_for_history.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alunos', function (Blueprint $table) {
            // Remove unique do CGM (mesmo aluno terá múltiplos registros)
            $table->dropUnique(['cgm']);
            
            // Adiciona campo para identificar registro ativo
            $table->boolean('ativo')->default(true)->after('situacao');
            
            // Referência ao registro anterior (para rastrear histórico)
            $table->foreignId('registro_anterior_id')
                ->nullable()
                ->after('ativo')
                ->constrained('alunos')
                ->nullOnDelete();
            
            // Índice para buscar registros ativos por CGM
            $table->index(['cgm', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::table('alunos', function (Blueprint $table) {
            $table->dropForeign(['registro_anterior_id']);
            $table->dropIndex(['cgm', 'ativo']);
            $table->dropColumn(['ativo', 'registro_anterior_id']);
            $table->unique('cgm');
        });
    }
};