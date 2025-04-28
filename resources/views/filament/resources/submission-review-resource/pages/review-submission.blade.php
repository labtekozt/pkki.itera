<x-filament::page>
    <form wire:submit.prevent="submitReview">
        {{ $this->form }}
        
        <div class="mt-6 flex justify-between items-center">
            <x-filament::button
                type="button"
                color="gray"
                tag="a"
                :href="route('filament.admin.resources.submission-reviews.index')"
            >
                Cancel
            </x-filament::button>
            
            <x-filament::button
                type="submit"
                color="primary"
                wire:loading.attr="disabled"
            >
                Submit Review
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
