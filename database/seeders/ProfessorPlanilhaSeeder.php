<?php

namespace Database\Seeders;

use App\Models\Escola;
use App\Models\Professor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class ProfessorPlanilhaSeeder extends Seeder
{
    private const SHEET_URL = 'https://docs.google.com/spreadsheets/d/1uQ2VfZJwTT_FrX7NE1ZGR_IA8QcObf9F93Jw0Z1zMv0/export?format=csv&gid=2051034793';

    private const CARGOS_PERMITIDOS = [
        'PROFESSOR DE EDUCAÇÃO INFANTIL',
        'PROFESSOR(A)',
        'PROFESSOR(A) - EDUCAÇÃO FÍSICA',
        'PROFESSOR(A) - PSS',
    ];

    public function run(): void
    {
        $this->command->info('📥 Baixando planilha de professores...');

        $response = Http::timeout(60)->get(self::SHEET_URL);

        if (!$response->ok()) {
            $this->command->error('❌ Falha ao baixar a planilha.');
            return;
        }

        $linhas = $this->parseCsv($response->body());

        if (empty($linhas)) {
            $this->command->warn('⚠️ CSV vazio.');
            return;
        }

        // Remove cabeçalho
        $header = array_map('trim', array_shift($linhas));

        /**
         * Mapeamento fixo pela ordem informada
         */
        $idxMatricula      = 0;
        $idxNome           = 1;
        $idxCargo          = 2;
        $idxLocalTrabalho  = 9;

        $professoresCriados = 0;
        $linhasIgnoradas = 0;

        // Cache de escolas por nome
        $escolasCache = Escola::all()
            ->keyBy(fn ($e) => mb_strtoupper(trim($e->nome)));

        foreach ($linhas as $linhaNumero => $linha) {
            $linhaReal = $linhaNumero + 2;

            $matricula = trim($linha[$idxMatricula] ?? '');
            $nome      = trim($linha[$idxNome] ?? '');
            $cargo     = mb_strtoupper(trim($linha[$idxCargo] ?? ''));
            $local     = mb_strtoupper(trim($linha[$idxLocalTrabalho] ?? ''));

            // Validações básicas
            if ($matricula === '' || $nome === '' || $local === '') {
                $linhasIgnoradas++;
                continue;
            }

            // Filtra cargos
            if (!in_array($cargo, self::CARGOS_PERMITIDOS, true)) {
                $linhasIgnoradas++;
                continue;
            }

            // Escola precisa existir
            if (!isset($escolasCache[$local])) {
                $linhasIgnoradas++;
                continue;
            }

            $escola = $escolasCache[$local];

            // Evita duplicar professor por matrícula
            $professor = Professor::firstOrCreate(
                ['matricula' => $matricula],
                [
                    'nome' => $nome,
                    'id_escola' => $escola->id,
                ]
            );

            if ($professor->wasRecentlyCreated) {
                $professoresCriados++;
                $this->command->info("✅ Professor criado: {$nome} ({$matricula}) - {$escola->nome}");
            }
        }

        $this->command->info('=====================================');
        $this->command->info('🎉 Importação de professores concluída');
        $this->command->line("👨‍🏫 Professores criados: {$professoresCriados}");
        $this->command->line("⚠️ Linhas ignoradas: {$linhasIgnoradas}");
        $this->command->info('=====================================');
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
