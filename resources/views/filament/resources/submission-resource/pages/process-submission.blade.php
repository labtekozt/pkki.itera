<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Process Submission
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
                                    Action
                                </x-filament::input.label>
                                
                                <x-filament::input.select 
                                    wire:model="action" 
                                    required
                                >
                                    <option value="">Select an action</option>
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
                                        Next Stage
                                    </x-filament::input.label>
                                    
                                    <x-filament::input.select 
                                        wire:model="targetStageId" 
                                        required
                                    >
                                        <option value="">Select next stage</option>
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
                                        Previous Stage
                                    </x-filament::input.label>
                                    
                                    <x-filament::input.select 
                                        wire:model="targetStageId" 
                                        required
                                    >
                                        <option value="">Select previous stage</option>
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
                                        Rejection Reason
                                    </x-filament::input.label>
                                    
                                    <x-filament::input.select 
                                        wire:model="rejectReason" 
                                        required
                                    >
                                        <option value="">Select a reason</option>
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
                                Comment
                            </x-filament::input.label>
                            
                            <x-filament::input.textarea 
                                wire:model="comment" 
                                required
                                placeholder="Enter your comment or justification for this action"
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
                            Cancel
                        </x-filament::button>
                        
                        <x-filament::button 
                            type="submit" 
                            :color="$action === 'reject' ? 'danger' : ($action === 'approve' || $action === 'complete' ? 'success' : 'primary')"
                        >
                            Process
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
