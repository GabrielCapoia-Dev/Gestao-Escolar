<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProfessorFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 1;
        
        $nome = fake()->name();
        $primeiroNome = explode(' ', $nome)[0];
        $sobrenome = explode(' ', $nome)[count(explode(' ', $nome)) - 1];
        
        return [
            'matricula' => 'PROF' . str_pad($counter++, 4, '0', STR_PAD_LEFT),
            'nome' => $nome,
            'email' => strtolower($primeiroNome . '.' . $sobrenome . '@escola.edu.br'),
            'telefone' => fake()->boolean(85) ? $this->gerarTelefoneBrasileiro() : null,
        ];
    }
    
    private function gerarTelefoneBrasileiro(): string
    {
        $ddd = fake()->numberBetween(11, 99);
        $numero = fake()->numberBetween(90000, 99999) . fake()->numberBetween(1000, 9999);
        return "({$ddd}) {$numero}";
    }
}