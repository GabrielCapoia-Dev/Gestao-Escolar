<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pautas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_avaliacao')->constrained('tipo_avaliacoes')->onDelete('cascade');
            $table->foreignId('id_componente_curricular')
                ->constrained('componentes_curriculares')
                ->cascadeOnDelete();

            $table->foreignId('id_serie')->constrained('series')->onDelete('cascade');
            $table->string('pauta');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pautas');
    }
};
