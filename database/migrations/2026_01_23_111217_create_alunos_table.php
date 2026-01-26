<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alunos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_turma')->constrained('turmas')->onDelete('cascade');
            $table->string('cgm')->unique();
            $table->string('nome');
            $table->date('data_nascimento')->nullable();
            $table->integer('idade');
            $table->enum('sexo', ['M', 'F']);
            $table->string('situacao');
            $table->date('data_matricula')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alunos');
    }
};
