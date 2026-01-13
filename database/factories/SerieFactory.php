<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SerieFactory extends Factory
{
    public function definition(): array
    {
        static $counter = 1;
        
        $series = [
            ['codigo' => 'SER001', 'nome' => 'Berçário'],
            ['codigo' => 'SER002', 'nome' => 'Maternal I'],
            ['codigo' => 'SER003', 'nome' => 'Maternal II'],
            ['codigo' => 'SER004', 'nome' => 'Jardim I'],
            ['codigo' => 'SER005', 'nome' => 'Jardim II'],
            ['codigo' => 'SER006', 'nome' => '1º Ano'],
            ['codigo' => 'SER007', 'nome' => '2º Ano'],
            ['codigo' => 'SER008', 'nome' => '3º Ano'],
            ['codigo' => 'SER009', 'nome' => '4º Ano'],
            ['codigo' => 'SER010', 'nome' => '5º Ano'],
            ['codigo' => 'SER011', 'nome' => '6º Ano'],
            ['codigo' => 'SER012', 'nome' => '7º Ano'],
            ['codigo' => 'SER013', 'nome' => '8º Ano'],
            ['codigo' => 'SER014', 'nome' => '9º Ano'],
            ['codigo' => 'SER015', 'nome' => '1º Ano EM'],
            ['codigo' => 'SER016', 'nome' => '2º Ano EM'],
            ['codigo' => 'SER017', 'nome' => '3º Ano EM'],
        ];
        
        $serie = $series[$counter - 1] ?? [
            'codigo' => 'SER' . str_pad($counter, 3, '0', STR_PAD_LEFT),
            'nome' => $counter . 'º Ano'
        ];
        
        $counter++;
        
        return $serie;
    }
}