<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200 dark:border-white/10">
                <th class="px-3 py-3"></th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-white">Escola</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-white">Série</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-white">Turma</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-white">Turno</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-white">Situação</th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-700 dark:text-white">Data</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
            @forelse($historico as $registro)
                <tr class="{{ $registro->ativo ? 'bg-primary-50 dark:bg-primary-500/10' : '' }}">
                    
                    {{-- Status --}}
                    <td class="px-3 py-3 whitespace-nowrap">
                        @if($registro->ativo)
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary-500">
                                <x-heroicon-s-check class="w-3 h-3 text-white" />
                            </span>
                        @else
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600">
                                <x-heroicon-s-minus class="w-3 h-3 text-gray-700 dark:text-white" />
                            </span>
                        @endif
                    </td>

                    {{-- Escola --}}
                    <td class="px-3 py-3 font-medium text-black dark:text-white">
                        {{ $registro->turma?->escola?->nome ?? '—' }}
                    </td>

                    {{-- Série --}}
                    <td class="px-3 py-3 text-gray-800 dark:text-white">
                        {{ $registro->turma?->serie?->nome ?? '—' }}
                    </td>

                    {{-- Turma --}}
                    <td class="px-3 py-3 text-gray-800 dark:text-white">
                        {{ $registro->turma?->nome ?? '—' }}
                    </td>

                    {{-- Turno --}}
                    <td class="px-3 py-3 text-gray-800 dark:text-white">
                        {{ match($registro->turma?->turno) {
                            'manha' => 'Manhã',
                            'tarde' => 'Tarde',
                            'noite' => 'Noite',
                            'integral' => 'Integral',
                            default => '—',
                        } }}
                    </td>

                    {{-- Situação --}}
                    <td class="px-3 py-3 whitespace-nowrap dark:text-white">
                        <x-filament::badge 
                            size="sm"
                            :color="match($registro->situacao) {
                                'Matriculado' => 'success',
                                'Desistente' => 'danger',
                                'Remanejado' => 'warning',
                                'Transferência' => 'info',
                                default => 'gray',
                            }"
                        >
                            {{ $registro->situacao }}
                        </x-filament::badge>
                    </td>

                    {{-- Data --}}
                    <td class="px-3 py-3 whitespace-nowrap text-gray-600 dark:text-white">
                        {{ $registro->created_at->format('d/m/Y H:i') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-3 py-8 text-center text-gray-600 dark:text-white">
                        Nenhum histórico encontrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="px-3 py-2 border-t border-gray-200 dark:border-white/10 text-right">
        <span class="text-xs text-gray-600 dark:text-white">
            {{ $historico->count() }} registro(s)
        </span>
    </div>
</div>
