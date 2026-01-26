<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacao_turma', function (Blueprint $table) {
            $table->foreignId('id_avaliacao')->constrained('avaliacoes')->onDelete('cascade');
            $table->foreignId('id_turma')->constrained('turmas')->onDelete('cascade');
            $table->primary(['id_avaliacao', 'id_turma']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacao_turma');
    }
};