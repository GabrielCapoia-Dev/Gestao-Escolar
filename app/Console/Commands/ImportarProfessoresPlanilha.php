<?php

namespace App\Console\Commands;

use App\Models\Escola;
use App\Models\Professor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportarProfessoresPlanilha extends Command
{
    protected $signature = 'professores:importar 
                            {--url= : URL da planilha (opcional, usa padrão se não informada)}
                            {--dry-run : Simula a importação sem salvar no banco}';

    protected $description = 'Importa e atualiza professores a partir de uma planilha do Google Sheets';

    private const DEFAULT_SHEET_URL = 'https://docs.google.com/spreadsheets/d/1uQ2VfZJwTT_FrX7NE1ZGR_IA8QcObf9F93Jw0Z1zMv0/export?format=csv&gid=2051034793';

    private const CARGOS_PERMITIDOS = [
        'PROFESSOR DE EDUCAÇÃO INFANTIL',
        'PROFESSOR(A)',
        'PROFESSOR(A) - EDUCAÇÃO FÍSICA',
        'PROFESSOR(A) - PSS',
    ];

    public function handle(): int
    {
        $url = $this->option('url') ?? self::DEFAULT_SHEET_URL;
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 Modo simulação ativado - nenhuma alteração será salva');
        }

        $this->info('📥 Baixando planilha de professores...');

        $response = Http::timeout(60)->get($url);

        if (!$response->ok()) {
            $this->error('❌ Falha ao baixar a planilha.');
            return Command::FAILURE;
        }

        $linhas = $this->parseCsv($response->body());

        if (empty($linhas)) {
            $this->warn('⚠️ CSV vazio.');
            return Command::FAILURE;
        }

        // Remove cabeçalho
        $header = array_map('trim', array_shift($linhas));

        // Mapeamento fixo pela ordem informada
        $idxMatricula = 0;
        $idxNome = 1;
        $idxCargo = 2;
        $idxLocalTrabalho = 9;

        $professoresCriados = 0;
        $professoresAtualizados = 0;
        $linhasIgnoradas = 0;

        // Cache de escolas por nome
        $escolasCache = Escola::all()
            ->keyBy(fn($e) => mb_strtoupper(trim($e->nome)));

        $this->info('📊 Processando ' . count($linhas) . ' linhas...');
        $this->newLine();

        $bar = $this->output->createProgressBar(count($linhas));
        $bar->start();

        foreach ($linhas as $linha) {
            $bar->advance();

            $matricula = trim($linha[$idxMatricula] ?? '');
            $nome = trim($linha[$idxNome] ?? '');
            $cargo = mb_strtoupper(trim($linha[$idxCargo] ?? ''));
            $local = mb_strtoupper(trim($linha[$idxLocalTrabalho] ?? ''));

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

            if ($dryRun) {
                // Simula verificando se existe
                $existe = Professor::where('matricula', $matricula)->exists();
                if ($existe) {
                    $professoresAtualizados++;
                } else {
                    $professoresCriados++;
                }
                continue;
            }

            // Atualiza ou cria professor pela matrícula
            $professor = Professor::updateOrCreate(
                ['matricula' => $matricula],
                [
                    'nome' => $nome,
                    'id_escola' => $escola->id,
                ]
            );

            if ($professor->wasRecentlyCreated) {
                $professoresCriados++;
            } elseif ($professor->wasChanged()) {
                $professoresAtualizados++;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('=====================================');
        $this->info('🎉 Importação de professores concluída');
        $this->newLine();
        
        $this->table(
            ['Métrica', 'Quantidade'],
            [
                ['👨‍🏫 Professores criados', $professoresCriados],
                ['🔄 Professores atualizados', $professoresAtualizados],
                ['⚠️ Linhas ignoradas', $linhasIgnoradas],
                ['📊 Total processado', count($linhas)],
            ]
        );

        $this->info('=====================================');

        return Command::SUCCESS;
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