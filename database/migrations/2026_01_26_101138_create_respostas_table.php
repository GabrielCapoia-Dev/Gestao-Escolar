<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_avaliacao')->constrained('avaliacoes')->onDelete('cascade');
            $table->foreignId('id_aluno')->constrained('alunos')->onDelete('cascade');
            $table->foreignId('id_pauta')->constrained('pautas')->onDelete('cascade');
            $table->foreignId('id_alternativa')->constrained('alternativas')->onDelete('cascade');
            $table->foreignId('id_professor')->constrained('professores')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['id_avaliacao', 'id_aluno', 'id_pauta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respostas');
    }
};