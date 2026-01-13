<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EscolaFactory extends Factory
{
    private static $escolasPreDefinidas = [
        ['codigo' => 'ESC001', 'nome' => 'Escola Municipal Professor João Silva'],
        ['codigo' => 'ESC002', 'nome' => 'Escola Estadual Professora Maria Santos'],
        ['codigo' => 'ESC003', 'nome' => 'EMEF Dom Pedro II'],
        ['codigo' => 'ESC004', 'nome' => 'EMEI Santos Dumont'],
        ['codigo' => 'ESC005', 'nome' => 'Colégio Machado de Assis'],
        ['codigo' => 'ESC006', 'nome' => 'Escola Municipal Villa-Lobos'],
        ['codigo' => 'ESC007', 'nome' => 'Escola Estadual Cecília Meireles'],
        ['codigo' => 'ESC008', 'nome' => 'EMEF Carlos Drummond de Andrade'],
        ['codigo' => 'ESC009', 'nome' => 'EMEI Monteiro Lobato'],
        ['codigo' => 'ESC010', 'nome' => 'Colégio Rui Barbosa'],
    ];
    
    private static $counter = 0;
    
    public function definition(): array
    {
        $escola = self::$escolasPreDefinidas[self::$counter] ?? [
            'codigo' => 'ESC' . str_pad(self::$counter + 1, 3, '0', STR_PAD_LEFT),
            'nome' => 'Escola ' . fake()->unique()->company(),
        ];
        
        self::$counter++;
        
        return [
            'codigo' => $escola['codigo'],
            'nome' => $escola['nome'],
            'telefone' => fake()->boolean(80) ? $this->gerarTelefoneBrasileiro() : null,
            'email' => fake()->boolean(70) ? fake()->unique()->safeEmail() : null,
        ];
    }
    
    private function gerarTelefoneBrasileiro(): string
    {
        $ddd = fake()->numberBetween(11, 99);
        $numero = fake()->numberBetween(90000, 99999) . fake()->numberBetween(1000, 9999);
        return "({$ddd}) {$numero}";
    }
}