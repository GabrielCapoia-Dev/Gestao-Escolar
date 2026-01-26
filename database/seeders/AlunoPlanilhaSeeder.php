<?php

namespace Database\Seeders;

use App\Models\Aluno;
use App\Models\Escola;
use App\Models\Serie;
use App\Models\Turma;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AlunoPlanilhaSeeder extends Seeder
{
    private const SHEET_URL = 'https://docs.google.com/spreadsheets/d/17ygjYltYvIQ00IoVTHG9o6POXEkBSfM3z1Y3dhkMqbM/export?format=csv&gid=1378048601';

    public function run(): void
    {
        $this->command->info('Baixando CSV da planilha...');

        $response = Http::timeout(60)->get(self::SHEET_URL);
        if (!$response->ok()) {
            $this->command->error('Falha ao baixar CSV.');
            return;
        }

        $linhas = $this->parseCsv($response->body());
        if (count($linhas) < 2) {
            $this->command->warn('CSV vazio.');
            return;
        }

        $header = array_map('trim', array_shift($linhas));

        $idxEscola   = array_search('Escola', $header);
        $idxSeriacao = array_search('Seriação', $header);
        $idxTurma    = array_search('Turma', $header);
        $idxTurno    = array_search('Turno', $header);

        $idxCgm           = 5;   // F
        $idxNomeAluno     = 6;   // G
        $idxDataNasc      = 7;   // H
        $idxIdade         = 8;   // I
        $idxSexo          = 9;   // J
        $idxSituacao      = 12;  // M
        $idxDataMatricula = 13;  // N

        $errosCsv = [];
        $processadas = 0;
        $ignoradas = 0;

        foreach ($linhas as $linha) {

            $cgm  = trim($linha[$idxCgm] ?? '');
            $nome = trim($linha[$idxNomeAluno] ?? '');

            if ($cgm === '' || $nome === '') {
                $ignoradas++;
                continue;
            }

            $escolaNome = trim($linha[$idxEscola] ?? '');
            $seriacao   = trim($linha[$idxSeriacao] ?? '');
            $turmaNome  = trim($linha[$idxTurma] ?? '');
            $turno      = strtolower(trim($linha[$idxTurno] ?? ''));

            // ===== VALIDA ESCOLA =====
            $escola = Escola::where('nome', $escolaNome)->first();
            if (!$escola) {
                $this->pushErro($errosCsv, $linha, 'Escola não encontrada');
                continue;
            }

            // ===== VALIDA SÉRIE =====
            $serieNome = $turno === 'integral'
                ? "{$seriacao} - Base"
                : $seriacao;

            $serie = Serie::where('nome', $serieNome)->first();
            if (!$serie) {
                $this->pushErro($errosCsv, $linha, 'Série não encontrada');
                continue;
            }

            // ===== VALIDA TURMA NA ESCOLA =====
            $turma = Turma::where([
                'id_escola' => $escola->id,
                'id_serie'  => $serie->id,
                'nome'      => $turmaNome,
                'turno'     => $turno,
            ])->first();

            if (!$turma) {
                $this->pushErro($errosCsv, $linha, 'Turma não encontrada na escola');
                continue;
            }

            // ===== SALVA / ATUALIZA ALUNO =====
            Aluno::updateOrCreate(
                ['cgm' => $cgm],
                [
                    'id_turma'        => $turma->id,
                    'nome'            => $nome,
                    'situacao'        => trim($linha[$idxSituacao] ?? ''),
                    'data_matricula'  => $this->normalizeDate($linha[$idxDataMatricula] ?? null),
                    'data_nascimento' => $this->normalizeDate($linha[$idxDataNasc] ?? null),
                    'idade'           => is_numeric($linha[$idxIdade] ?? null) ? (int)$linha[$idxIdade] : null,
                    'sexo'            => trim($linha[$idxSexo] ?? ''),
                ]
            );

            $processadas++;
        }

        if (!empty($errosCsv)) {
            $path = 'alunos_sem_turma.csv';
            Storage::put($path, $this->buildCsv($errosCsv));
            $this->command->warn("⚠️ Alunos com erro exportados para storage/app/{$path}");
        }

        $this->command->info('=====================================');
        $this->command->info('Importação finalizada');
        $this->command->line("Processadas: {$processadas}");
        $this->command->line("Ignoradas: {$ignoradas}");
        $this->command->line("Com erro de vínculo: " . count($errosCsv));
        $this->command->info('=====================================');
    }

    private function pushErro(array &$csv, array $linha, string $motivo): void
    {
        $csv[] = [
            $linha[5] ?? '',   // CGM
            $linha[6] ?? '',   // Nome
            $linha[0] ?? '',   // Escola
            $linha[1] ?? '',   // Série
            $linha[2] ?? '',   // Turma
            $linha[3] ?? '',   // Turno
            $motivo,
        ];
    }

    private function buildCsv(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['CGM', 'Aluno', 'Escola', 'Série', 'Turma', 'Turno', 'Motivo']);

        foreach ($rows as $row) {
            fputcsv($out, $row);
        }

        rewind($out);
        return stream_get_contents($out);
    }

    private function parseCsv(string $csv): array
    {
        $rows = [];
        $fh = fopen('php://memory', 'r+');
        fwrite($fh, $csv);
        rewind($fh);

        while (($row = fgetcsv($fh, 0, ',')) !== false) {
            $rows[] = $row;
        }

        fclose($fh);
        return $rows;
    }

    private function normalizeDate(?string $value): ?string
    {
        if (!$value) return null;

        $value = trim($value);

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            $dt = \DateTime::createFromFormat('d/m/Y', $value);
            return $dt ? $dt->format('Y-m-d') : null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
