<?php

namespace Database\Factories;

use App\Models\Escola;
use App\Models\Serie;
use Illuminate\Database\Eloquent\Factories\Factory;

class TurmaFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 1;
        
        $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $turnos = ['Manhã', 'Tarde', 'Noite', 'Integral'];
        
        $letra = fake()->randomElement($letras);
        $turno = fake()->randomElement($turnos);
        
        return [
            'codigo' => 'TUR' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
            'nome' => "Turma {$letra} - {$turno}",
            'id_serie' => Serie::inRandomOrder()->first()?->id ?? Serie::factory(),
            'id_escola' => Escola::inRandomOrder()->first()?->id ?? Escola::factory(),
        ];
    }
}