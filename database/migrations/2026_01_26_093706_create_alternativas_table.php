<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alternativas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_avaliacao')->constrained('tipo_avaliacoes')->onDelete('cascade');
            $table->string('texto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alternativas');
    }
};