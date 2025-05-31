<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Proses Pengajuan
        </x-slot>
        
        <x-slot name="headerEnd">
            <div>
                <span 
                    @class([
                        'px-2 py-1 rounded-full text-xs font-medium',
                        'bg-gray-100 text-gray-800' => $this->getWorkflowStatus()['color'] === 'gray',
                        'bg-info-100 text-info-800' => $this->getWorkflowStatus()['color'] === 'info',
                        'bg-primary-100 text-primary-800' => $this->getWorkflowStatus()['color'] === 'primary',
                        'bg-warning-100 text-warning-800' => $this->getWorkflowStatus()['color'] === 'warning',
                        'bg-success-100 text-success-800' => $this->getWorkflowStatus()['color'] === 'success',
                        'bg-danger-100 text-danger-800' => $this->getWorkflowStatus()['color'] === 'danger',
                    ])
                >
                    {{ $this->getWorkflowStatus()['label'] }}
                </span>
            </div>
        </x-slot>
        
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <form wire:submit="processAction" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-filament::input.wrapper>
                                <x-filament::input.label required>
                                    Tindakan
                                </x-filament::input.label>
                                
                                <x-filament::input.select 
                                    wire:model="action" 
                                    required
                                >
                                    <option value="">Pilih tindakan</option>
                                    @foreach ($this->getAvailableActions() as $availableAction)
                                        <option value="{{ $availableAction['id'] }}">
                                            {{ $availableAction['label'] }}
                                        </option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                        </div>
                        
                        @if ($action === 'advance_stage' && count($this->getNextStages()) > 0)
                            <div>
                                <x-filament::input.wrapper>
                                    <x-filament::input.label required>
                                        Tahapan Selanjutnya
                                    </x-filament::input.label>
                                    
                                    <x-filament::input.select 
                                        wire:model="targetStageId" 
                                        required
                                    >
                                        <option value="">Pilih tahapan selanjutnya</option>
                                        @foreach ($this->getNextStages() as $id => $name)
                                            <option value="{{ $id }}">
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        @elseif ($action === 'return_stage' && count($this->getPreviousStages()) > 0)
                            <div>
                                <x-filament::input.wrapper>
                                    <x-filament::input.label required>
                                        Tahapan Sebelumnya
                                    </x-filament::input.label>
                                    
                                    <x-filament::input.select 
                                        wire:model="targetStageId" 
                                        required
                                    >
                                        <option value="">Pilih tahapan sebelumnya</option>
                                        @foreach ($this->getPreviousStages() as $id => $name)
                                            <option value="{{ $id }}">
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        @elseif ($action === 'reject')
                            <div>
                                <x-filament::input.wrapper>
                                    <x-filament::input.label required>
                                        Alasan Penolakan
                                    </x-filament::input.label>
                                    
                                    <x-filament::input.select 
                                        wire:model="rejectReason" 
                                        required
                                    >
                                        <option value="">Pilih alasan</option>
                                        @foreach ($this->getRejectReasons() as $id => $name)
                                            <option value="{{ $id }}">
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        @endif
                    </div>
                    
                    <div>
                        <x-filament::input.wrapper>
                            <x-filament::input.label required>
                                Komentar
                            </x-filament::input.label>
                            
                            <x-filament::input.textarea 
                                wire:model="comment" 
                                required
                                placeholder="Masukkan komentar atau justifikasi untuk tindakan ini"
                            />
                        </x-filament::input.wrapper>
                    </div>
                    
                    <div class="flex items-center justify-end gap-x-3">
                        <x-filament::button 
                            type="button"
                            color="gray"
                            tag="a"
                            :href="$this->getResource()::getUrl('view', ['record' => $record])"
                        >
                            Batal
                        </x-filament::button>
                        
                        <x-filament::button 
                            type="submit" 
                            :color="$action === 'reject' ? 'danger' : ($action === 'approve' || $action === 'complete' ? 'success' : 'primary')"
                        >
                            Proses
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
