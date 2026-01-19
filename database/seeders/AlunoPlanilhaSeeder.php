<?php

namespace Database\Seeders;

use App\Models\Escola;
use App\Models\Serie;
use App\Models\Turma;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class AlunoPlanilhaSeeder extends Seeder
{
    private const SHEET_URL = 'https://docs.google.com/spreadsheets/d/17ygjYltYvIQ00IoVTHG9o6POXEkBSfM3z1Y3dhkMqbM/export?format=csv&gid=1378048601';

    public function run(): void
    {
        $this->command->info("Baixando CSV da planilha...");

        $response = Http::timeout(60)->get(self::SHEET_URL);

        if (!$response->ok()) {
            $this->command->error('Falha ao baixar CSV da planilha.');
            return;
        }

        $csv = $response->body();
        $linhas = $this->parseCsv($csv);

        if (empty($linhas)) {
            $this->command->warn('CSV vazio ou sem dados.');
            return;
        }

        // Cabeçalho
        $header = array_map('trim', array_shift($linhas));

        // Mapeia índices
        $idxEscola   = array_search('Escola', $header);
        $idxSeriacao = array_search('Seriação', $header);
        $idxTurma    = array_search('Turma', $header);
        $idxTurno    = array_search('Turno', $header);

        if ($idxEscola === false || $idxSeriacao === false || $idxTurma === false || $idxTurno === false) {
            $this->command->error('Cabeçalho da planilha não corresponde aos nomes esperados.');
            return;
        }

        $escolasCriadas = 0;
        $seriesCriadas = 0;
        $turmasCriadas = 0;
        $linhasProcessadas = 0;
        $linhasIgnoradas = 0;

        // Cache em memória
        $escolasCache = [];
        $seriesCache = [];
        $turmasCache = [];

        foreach ($linhas as $linhaNumero => $linha) {
            $linhaReal = $linhaNumero + 2;

            $escolaNome  = trim($linha[$idxEscola] ?? '');
            $seriacao    = trim($linha[$idxSeriacao] ?? '');
            $turmaLetra  = trim($linha[$idxTurma] ?? '');
            $turno       = trim($linha[$idxTurno] ?? '');

            $turno = trim($linha[$idxTurno] ?? '');
            $turno = strtolower($turno);

            // Ignora "Sem Seriação" e linhas incompletas
            if ($escolaNome === '' || $seriacao === '' || $seriacao === 'Sem Seriação' || $turmaLetra === '' || $turno === '') {
                $linhasIgnoradas++;
                continue;
            }

            // 1. ESCOLA
            if (!isset($escolasCache[$escolaNome])) {
                $escola = Escola::firstOrCreate(
                    ['nome' => $escolaNome],
                    [
                        'codigo' => 'ESC' . str_pad((Escola::max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT),
                        'telefone' => null,
                        'email' => null,
                    ]
                );

                if ($escola->wasRecentlyCreated) {
                    $escolasCriadas++;
                    $this->command->info("✅ Escola criada: {$escolaNome}");
                }

                $escolasCache[$escolaNome] = $escola;
            }

            $escola = $escolasCache[$escolaNome];

            // 2. SÉRIE (regra do integral → Base)
            $serieNome = $seriacao;

            if ($turno === 'integral') {
                $serieNome = "{$seriacao} - Base";
            }

            if (!isset($seriesCache[$serieNome])) {

                $serie = Serie::firstOrCreate(
                    ['nome' => $serieNome],
                    [
                        'codigo' => 'SER' . str_pad((Serie::max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT),
                    ]
                );

                if ($serie->wasRecentlyCreated) {
                    $seriesCriadas++;
                    $this->command->info("✅ Série criada: {$serieNome}");
                }

                $seriesCache[$serieNome] = $serie;
            }

            $serie = $seriesCache[$serieNome];

            // 3. TURMA
            $turmaKey = "{$escola->id}|{$serie->id}|{$turmaLetra}|{$turno}";

            if (!isset($turmasCache[$turmaKey])) {
                $turma = Turma::firstOrCreate(
                    [
                        'id_escola' => $escola->id,
                        'id_serie' => $serie->id,
                        'nome' => $turmaLetra,
                        'turno' => $turno,
                    ],
                    ['codigo' => 'TUR' . str_pad((Turma::max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT)]
                );

                if ($turma->wasRecentlyCreated) {
                    $turmasCriadas++;
                    $this->command->info("✅ Turma criada: {$seriacao} - {$turmaLetra} ({$turno}) - {$escolaNome}");
                }

                $turmasCache[$turmaKey] = $turma;
            }

            $linhasProcessadas++;
        }

        // LOG FINAL
        $this->command->info("=====================================");
        $this->command->info("🎉 Importação concluída!");
        $this->command->line("📊 Linhas processadas: {$linhasProcessadas}");
        $this->command->line("⚠️  Linhas ignoradas: {$linhasIgnoradas}");
        $this->command->line("🏫 Escolas criadas: {$escolasCriadas}");
        $this->command->line("📚 Séries criadas: {$seriesCriadas}");
        $this->command->line("👥 Turmas criadas: {$turmasCriadas}");
        $this->command->info("=====================================");
    }

    private function parseCsv(string $csv): array
    {
        $linhas = [];
        $fh = fopen('php://memory', 'r+');
        fwrite($fh, $csv);
        rewind($fh);

        while (($row = fgetcsv($fh, 0, ',')) !== false) {
            $linhas[] = $row;
        }

        fclose($fh);
        return $linhas;
    }
}
