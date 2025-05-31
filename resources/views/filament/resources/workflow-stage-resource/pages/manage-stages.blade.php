<x-filament::page>
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight">
            Kelola Tahapan Alur Kerja untuk "{{ $record->name }}"
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Konfigurasi berbagai tahapan dalam alur kerja peninjauan untuk jenis pengajuan ini.
        </p>
    </div>

    <div class="space-y-6">
        <x-filament::section>
            {{ $this->form }}

            <x-slot name="footer">
                <div class="flex items-center gap-x-3">
                    <x-filament::button wire:click="create" wire:loading.attr="disabled">
                        Tambah Tahapan
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::section>

        {{ $this->table }}
    </div>
</x-filament::page>