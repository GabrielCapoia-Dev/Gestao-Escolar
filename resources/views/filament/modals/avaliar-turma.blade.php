<div
    x-data="{
        respostas: @js($respostasExistentes ?? []),
        observacoes: @js($observacoesExistentes ?? []),
        alternativasComObs: @js($alternativasComObservacao ?? []),
        pautas: @js($pautasArray ?? []),
        alternativasPorTipo: @js(collect($alternativasPorTipo)->map(fn($alts) => $alts->toArray())->toArray()),
        pautaAtualIndex: 0,
        salvando: false,
        
        get pautaAtual() {
            return this.pautas[this.pautaAtualIndex] || null;
        },
        
        get totalPautas() {
            return this.pautas.length;
        },
        
        get alternativasAtuais() {
            if (!this.pautaAtual) return [];
            return this.alternativasPorTipo[this.pautaAtual.id_tipo_avaliacao] || [];
        },
        
        anteriorPauta() {
            if (this.pautaAtualIndex > 0) {
                this.pautaAtualIndex--;
            }
        },
        
        proximaPauta() {
            if (this.pautaAtualIndex < this.totalPautas - 1) {
                this.pautaAtualIndex++;
            }
        },
        
        irParaPauta(index) {
            if (index >= 0 && index < this.totalPautas) {
                this.pautaAtualIndex = index;
            }
        },
        
        precisaObservacao(alternativaId) {
            return this.alternativasComObs.includes(parseInt(alternativaId));
        },
        
        getRespostaAluno(alunoId) {
            if (!this.pautaAtual) return '';
            return this.respostas[alunoId]?.[this.pautaAtual.id] || '';
        },
        
        getObservacaoAluno(alunoId) {
            if (!this.pautaAtual) return '';
            return this.observacoes[alunoId]?.[this.pautaAtual.id] || '';
        },
        
        getProgressoAluno(alunoId) {
            const respostasAluno = this.respostas[alunoId] || {};
            return Object.keys(respostasAluno).filter(id => 
                this.pautas.some(p => p.id == id)
            ).length;
        },
        
        getProgressoPauta() {
            if (!this.pautaAtual) return 0;
            const pautaId = this.pautaAtual.id;
            let count = 0;
            const alunosIds = @js($alunos->pluck('id')->toArray());
            for (const alunoId of alunosIds) {
                if (this.respostas[alunoId]?.[pautaId]) {
                    count++;
                }
            }
            return count;
        },
        
        async salvarResposta(alunoId, alternativaId, observacao = null) {
            if (!alternativaId || !this.pautaAtual) return;
            
            const pautaId = this.pautaAtual.id;
            this.salvando = true;
            
            try {
                const response = await fetch('{{ route("avaliacao.salvar-resposta") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id_avaliacao: {{ $avaliacao->id }},
                        id_turma: {{ $turmaId }},
                        id_aluno: alunoId,
                        id_pauta: pautaId,
                        id_alternativa: alternativaId,
                        observacao: observacao
                    })
                });
                
                if (response.ok) {
                    this.respostas[alunoId] = this.respostas[alunoId] || {};
                    this.respostas[alunoId][pautaId] = parseInt(alternativaId);
                    
                    if (observacao !== null) {
                        this.observacoes[alunoId] = this.observacoes[alunoId] || {};
                        this.observacoes[alunoId][pautaId] = observacao;
                    }
                }
            } catch (error) {
                console.error('Erro ao salvar:', error);
            } finally {
                this.salvando = false;
            }
        },
        
        async salvarObservacao(alunoId) {

            if (!this.pautaAtual) return;

            const pautaId = this.pautaAtual.id;

            const alternativaId = this.respostas[alunoId]?.[pautaId];

            const observacao = this.observacoes[alunoId]?.[pautaId] ?? '';

            this.salvando = true;

            try {

                await fetch('{{ route("avaliacao.salvar-resposta") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        id_avaliacao: {{ $avaliacao->id }},
                        id_turma: {{ $turmaId }},
                        id_aluno: alunoId,
                        id_pauta: pautaId,
                        id_alternativa: alternativaId,
                        observacao: observacao
                    })
                });

            } finally {
                this.salvando = false;
            }
        },
        
        async avaliarTodos(alternativaId) {
            if (!alternativaId || !this.pautaAtual) return;
            
            if (this.precisaObservacao(alternativaId)) {
                alert('Esta alternativa requer observação individual. Por favor, avalie cada aluno separadamente.');
                return;
            }
            
            this.salvando = true;
            const pautaId = this.pautaAtual.id;
            const alunosIds = @js($alunos->pluck('id')->toArray());
            
            for (const alunoId of alunosIds) {
                this.respostas[alunoId] = this.respostas[alunoId] || {};
                this.respostas[alunoId][pautaId] = parseInt(alternativaId);
                
                try {
                    await fetch('{{ route("avaliacao.salvar-resposta") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id_avaliacao: {{ $avaliacao->id }},
                            id_turma: {{ $turmaId }},
                            id_aluno: alunoId,
                            id_pauta: pautaId,
                            id_alternativa: alternativaId,
                            observacao: null
                        })
                    });
                } catch (error) {
                    console.error('Erro ao salvar:', error);
                }
            }
            
            this.salvando = false;
        },
        
        setObservacao(alunoId, valor) {
            if (!this.pautaAtual) return;
            this.observacoes[alunoId] = this.observacoes[alunoId] || {};
            this.observacoes[alunoId][this.pautaAtual.id] = valor;
        }
    }">

    {{-- Indicador de salvamento --}}
    <div x-show="salvando" class="fixed top-4 right-4 z-50">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-100">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Salvando...
        </span>
    </div>

    @if($pautas->count() > 0)
    {{-- Navegação de Pautas --}}
    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">

        {{-- Header com navegação --}}
        <div class="flex items-center justify-between mb-4">
            <button
                @click="anteriorPauta()"
                :disabled="pautaAtualIndex === 0"
                :class="pautaAtualIndex === 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="p-2 rounded-full bg-gray-100 dark:bg-gray-700 transition-colors">
                <x-heroicon-s-chevron-left class="w-6 h-6 text-gray-700 dark:text-white" />
            </button>

            <div class="text-center flex-1 px-4">
                <div class="flex items-center justify-center gap-2 mb-2">
                    <x-filament::badge size="md" color="primary">
                        <span x-text="pautaAtual?.tipo_avaliacao"></span>
                    </x-filament::badge>
                    <x-filament::badge size="md" color="gray">
                        <span x-text="pautaAtual?.componente"></span>
                    </x-filament::badge>
                </div>

                {{-- Nome da pauta em destaque --}}
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white leading-tight" x-text="pautaAtual?.pauta"></h3>

                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Pauta <span x-text="pautaAtualIndex + 1"></span> de <span x-text="totalPautas"></span>
                    •
                    <span x-text="getProgressoPauta()"></span> de {{ $alunos->count() }} avaliados
                </p>
            </div>

            <button
                @click="proximaPauta()"
                :disabled="pautaAtualIndex === totalPautas - 1"
                :class="pautaAtualIndex === totalPautas - 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-gray-200 dark:hover:bg-gray-600'"
                class="p-2 rounded-full bg-gray-100 dark:bg-gray-700 transition-colors">
                <x-heroicon-s-chevron-right class="w-6 h-6 text-gray-700 dark:text-white" />
            </button>
        </div>

        {{-- Indicadores de página (bolinhas) --}}
        <div class="flex items-center justify-center gap-1 flex-wrap">
            <template x-for="(pauta, index) in pautas" :key="index">
                <button
                    @click="irParaPauta(index)"
                    :class="index === pautaAtualIndex 
                            ? 'bg-primary-500 w-3 h-3' 
                            : 'bg-gray-300 dark:bg-gray-600 w-2 h-2 hover:bg-gray-400 dark:hover:bg-gray-500'"
                    class="rounded-full transition-all"
                    :title="pauta.pauta"></button>
            </template>
        </div>

        {{-- Aplicar a todos --}}
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
                Aplicar a todos os alunos:
            </label>
            <select
                @change="avaliarTodos($event.target.value); $event.target.value = ''"
                class="w-full rounded bg-white text-gray-900 border-gray-300 dark:bg-gray-700 dark:text-white dark:border-gray-600 text-sm focus:ring-primary-500 focus:border-primary-500">
                <option value="">Selecione uma alternativa...</option>
                <template x-for="alt in alternativasAtuais" :key="alt.id">
                    <option :value="alt.id">
                        <span x-text="alt.texto"></span>
                        <span x-show="alt.tem_observacao"> ⚠️</span>
                    </option>
                </template>
            </select>
        </div>
    </div>

    {{-- Tabela de Alunos --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-white/10">
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-white w-1/2">
                        Aluno
                    </th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-white w-1/2">
                        Resposta
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach($alunos as $aluno)
                <tr
                    :class="getProgressoAluno({{ $aluno->id }}) === totalPautas ? 'bg-primary-50 dark:bg-primary-500/10' : ''">

                    {{-- Aluno --}}
                    <td class="px-3 py-3">
                        <div class="flex items-center gap-2">
                            <span
                                x-show="getProgressoAluno({{ $aluno->id }}) === totalPautas"
                                class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary-500 flex-shrink-0">
                                <x-heroicon-s-check class="w-3 h-3 text-white" />
                            </span>
                            <span
                                x-show="getProgressoAluno({{ $aluno->id }}) !== totalPautas"
                                class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600 flex-shrink-0">
                                <span class="text-xs font-medium text-gray-700 dark:text-white" x-text="getProgressoAluno({{ $aluno->id }})"></span>
                            </span>

                            <div>
                                <p class="font-medium text-black dark:text-white">{{ $aluno->nome }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">CGM: {{ $aluno->cgm }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- Resposta --}}
                    <td class="px-3 py-3">
                        <div class="space-y-2">
                            <select
                                :value="getRespostaAluno({{ $aluno->id }})"
                                @change="salvarResposta({{ $aluno->id }}, $event.target.value)"
                                class="w-full rounded bg-white text-gray-900 border-gray-300 dark:bg-gray-700 dark:text-white dark:border-gray-600 text-sm focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Selecione...</option>
                                <template x-for="alt in alternativasAtuais" :key="alt.id">
                                    <option :value="alt.id" :selected="getRespostaAluno({{ $aluno->id }}) == alt.id">
                                        <span x-text="alt.texto"></span>
                                        <span x-show="alt.tem_observacao"> ⚠️</span>
                                    </option>
                                </template>
                            </select>

                            {{-- Campo de observação --}}
                            <div
                                x-show="getRespostaAluno({{ $aluno->id }}) && precisaObservacao(getRespostaAluno({{ $aluno->id }}))"
                                x-transition>
                                <textarea
                                    :value="getObservacaoAluno({{ $aluno->id }})"
                                    @input="setObservacao({{ $aluno->id }}, $event.target.value)"
                                    @blur="salvarObservacao({{ $aluno->id }})"
                                    placeholder="Descreva a observação..."
                                    rows="2"
                                    class="w-full text-xs rounded bg-amber-50 text-gray-900 border-amber-300 dark:bg-amber-900/20 dark:text-white dark:border-amber-600 focus:ring-amber-500 focus:border-amber-500 placeholder-gray-400 dark:placeholder-gray-500"></textarea>
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                    <x-heroicon-s-exclamation-triangle class="w-3 h-3 inline" />
                                    Observação obrigatória
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Footer --}}
    <div class="px-3 py-3 border-t border-gray-200 dark:border-white/10 flex items-center justify-between mt-4">
        <span class="text-xs text-gray-600 dark:text-white">
            {{ $alunos->count() }} aluno(s) • {{ $pautas->count() }} pauta(s) • {{ $alunos->count() * $pautas->count() }} respostas esperadas
        </span>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            As respostas são salvas automaticamente • ⚠️ = requer observação
        </span>
    </div>

    @else
    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
        <x-heroicon-o-document-text class="w-12 h-12 mx-auto mb-2 opacity-50" />
        <p>Nenhuma pauta disponível para avaliação nesta turma.</p>
        <p class="text-sm mt-1">Verifique se você está vinculado aos componentes desta turma.</p>
    </div>
    @endif
</div>