<?php

namespace Database\Seeders;

use App\Models\ComponenteCurricular;
use App\Models\Serie;
use Illuminate\Database\Seeder;

class ComponenteCurricularSeeder extends Seeder
{
    public function run(): void
    {
        $componentesBase = [
            'portugues' => 'Português',
            'matematica' => 'Matemática',
            'historia' => 'História',
            'geografia' => 'Geografia',
            'ciencias' => 'Ciências',
            'arte' => 'Arte',
            'educacao_fisica' => 'Educação Física',
            'ensino_religioso' => 'Ensino Religioso',
            'ingles' => 'Inglês',
            'corpo_gesto_movimento' => 'Corpo, Gesto e Movimento',
        ];

        $componentes = [];

        foreach ($componentesBase as $codigo => $nome) {
            $componentes[$codigo] = ComponenteCurricular::firstOrCreate([
                'codigo' => $codigo,
                'nome' => $nome,
            ]);
        }

        // Educação Infantil
        $infantilSeries = Serie::whereIn('nome', [
            'Berçário',
            'Maternal I',
            'Maternal II',
            'Jardim I',
            'Jardim II',
        ])->get();

        foreach ($infantilSeries as $serie) {
            $serie->componentesCurriculares()->sync([
                $componentes['corpo_gesto_movimento']->id,
                $componentes['arte']->id,
                $componentes['ensino_religioso']->id,
            ]);
        }

        // Ensino Fundamental
        $fundamentalSeries = Serie::where('nome', 'like', '%Ano')->get();

        foreach ($fundamentalSeries as $serie) {
            $serie->componentesCurriculares()->sync([
                $componentes['portugues']->id,
                $componentes['matematica']->id,
                $componentes['historia']->id,
                $componentes['geografia']->id,
                $componentes['ciencias']->id,
                $componentes['arte']->id,
                $componentes['educacao_fisica']->id,
            ]);
        }
    }
}
