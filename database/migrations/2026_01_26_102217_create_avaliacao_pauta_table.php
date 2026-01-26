<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacao_pauta', function (Blueprint $table) {
            $table->foreignId('id_avaliacao')->constrained('avaliacoes')->onDelete('cascade');
            $table->foreignId('id_pauta')->constrained('pautas')->onDelete('cascade');
            $table->primary(['id_avaliacao', 'id_pauta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacao_pauta');
    }
};