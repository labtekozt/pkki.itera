<x-filament-widgets::widget class="col-span-full">
    <x-filament::section>
        <x-slot name="heading">
            <span class="text-xl md:text-2xl font-bold">Progress Pengajuan</span>
        </x-slot>

        @if (!$submission)
            <div class="flex items-center justify-center p-6">
                <p class="text-lg text-gray-500">Tidak ada pengajuan yang dipilih</p>
            </div>
        @else
            <div class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    
                    <div class="bg-primary-50 dark:bg-primary-900/20 p-4 md:p-6 rounded-lg shadow-sm">
                        <h3 class="text-sm md:text-base font-medium text-primary-700 dark:text-primary-400">Tahap Saat Ini</h3>
                        <p class="text-2xl md:text-3xl font-bold">{{ $submission->currentStage?->name ?? 'Belum Ada' }}</p>
                    </div>
                    
                    <div class="bg-primary-50 dark:bg-primary-900/20 p-4 md:p-6 rounded-lg shadow-sm">
                        <h3 class="text-sm md:text-base font-medium text-primary-700 dark:text-primary-400">Dokumen</h3>
                        <p class="text-2xl md:text-3xl font-bold">{{ $statistics['documents_approved'] }}/{{ $statistics['documents_total'] }}</p>
                    </div>
                    
                    <div class="bg-primary-50 dark:bg-primary-900/20 p-4 md:p-6 rounded-lg shadow-sm">
                        <h3 class="text-sm md:text-base font-medium text-primary-700 dark:text-primary-400">Permintaan Revisi</h3>
                        <p class="text-2xl md:text-3xl font-bold">{{ $statistics['revisions_requested'] }}</p>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="relative">
                    <!-- Progress Line -->
                    <div class="absolute top-5 left-5 h-full w-1 bg-gray-300 dark:bg-gray-700" style="left: 1.5rem;"></div>
                    
                    <ol class="relative space-y-8 md:space-y-10">
                        @foreach ($progressSteps as $step)
                            <li class="ml-8 md:ml-10">
                                <div class="flex gap-4 items-start">
                                    <span 
                                        @class([
                                            'absolute -left-3 flex items-center justify-center rounded-full p-2 ring-8 ring-white dark:ring-gray-900',
                                            'bg-success-500' => $step['status'] === 'completed',
                                            'bg-primary-500' => $step['status'] === 'current',
                                            'bg-gray-400 dark:bg-gray-600' => $step['status'] === 'upcoming',
                                        ])
                                        style="height: 3rem; width: 3rem; margin-left: 0;"
                                    >
                                        @if ($step['status'] === 'completed')
                                            <x-heroicon-s-check class="h-6 w-6 text-white" />
                                        @elseif ($step['status'] === 'current')
                                            <x-heroicon-s-arrow-right class="h-6 w-6 text-white" />
                                        @else
                                            <x-heroicon-o-clock class="h-6 w-6 text-white dark:text-gray-300" />
                                        @endif
                                    </span>
                                    
                                    <div class="flex-1 pl-2 md:pl-4">
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                                {{ $step['name'] }}
                                            </h3>
                                            <span class="text-sm font-medium rounded-full px-3 py-1 
                                                @if ($step['status'] === 'completed') bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-300 @endif
                                                @if ($step['status'] === 'current') bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300 @endif
                                                @if ($step['status'] === 'upcoming') bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 @endif
                                            ">
                                                @if ($step['status'] === 'completed') Selesai @elseif ($step['status'] === 'current') Sedang Berjalan @else Menunggu @endif
                                            </span>
                                        </div>
                                        
                                        @if ($step['date'])
                                            <time class="block mb-2 text-sm md:text-base font-normal leading-none text-gray-500 dark:text-gray-400">
                                                {{ $step['date'] }}
                                                @if ($step['days_spent'] > 0)
                                                    <span class="ml-2">({{ $step['days_spent'] }} hari)</span>
                                                @endif
                                            </time>
                                        @endif
                                        
                                        <p class="text-base md:text-lg font-normal text-gray-600 dark:text-gray-400 my-2">{{ $step['description'] }}</p>
                                        
                                        @if (count($step['actions']) > 0)
                                            <div class="mt-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                                                <h4 class="text-base font-medium text-gray-900 dark:text-white">Aktivitas Terbaru</h4>
                                                <ul class="mt-2 space-y-2">
                                                    @foreach ($step['actions'] as $action)
                                                        <li class="text-sm md:text-base bg-gray-50 dark:bg-gray-800/50 p-3 rounded-md">
                                                            <div class="flex flex-wrap justify-between">
                                                                <span class="font-medium">{{ $action['action'] }}</span>
                                                                <span class="text-gray-500 dark:text-gray-400 text-sm">{{ $action['date'] }} by {{ $action['user'] }}</span>
                                                            </div>
                                                            @if ($action['comment'])
                                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $action['comment'] }}</p>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
