<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $statePath = $getStatePath();
        $isMultiple = $getIsMultiple();
        $acceptedFileTypes = $getAcceptedFileTypes();
    @endphp

    <div
        x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            selectedMedia: @js($getSelectedMedia()->toArray()),
            isMultiple: @js($isMultiple),
            statePath: @js($statePath),
            acceptedFileTypes: @js($acceptedFileTypes),
            
            init() {
                // Listen for media selection from modal
                window.addEventListener('media-selected', (event) => {
                    if (event.detail.statePath === this.statePath) {
                        this.state = event.detail.selectedIds;
                        this.selectedMedia = event.detail.selectedMedia || [];
                    }
                });
            },
            
            openPicker() {
                $dispatch('open-media-picker', {
                    statePath: this.statePath,
                    multiple: this.isMultiple,
                    acceptedFileTypes: this.acceptedFileTypes,
                    currentSelection: Array.isArray(this.state) ? this.state : (this.state ? [this.state] : [])
                });
            },
            
            removeMedia(mediaId) {
                if (Array.isArray(this.state)) {
                    this.state = this.state.filter(id => id !== mediaId);
                } else {
                    this.state = [];
                }
                this.selectedMedia = this.selectedMedia.filter(m => m.id !== mediaId);
            },
            
            getMediaUrl(media) {
                if (media.url) return media.url;
                if (media.path) return '/storage/' + media.path;
                return '';
            }
        }"
        x-on:media-selected.window="
            if ($event.detail.statePath === statePath) {
                state = $event.detail.selectedIds;
                selectedMedia = $event.detail.selectedMedia || [];
            }
        "
        class="space-y-2"
    >
        {{-- Selected Media Preview --}}
        <template x-if="selectedMedia && selectedMedia.length > 0">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                <template x-for="media in selectedMedia" :key="media.id">
                    <div class="group relative aspect-square overflow-hidden rounded-lg border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                        <template x-if="media.mime_type && media.mime_type.startsWith('image/')">
                            <img 
                                :src="getMediaUrl(media)" 
                                :alt="media.name"
                                class="h-full w-full object-cover"
                            >
                        </template>
                        <template x-if="!media.mime_type || !media.mime_type.startsWith('image/')">
                            <div class="flex h-full w-full items-center justify-center">
                                <x-heroicon-o-document class="h-12 w-12 text-gray-400" />
                            </div>
                        </template>
                        
                        {{-- Remove Button --}}
                        <button
                            type="button"
                            x-on:click.stop.prevent="removeMedia(media.id)"
                            class="absolute right-1 top-1 rounded-full bg-danger-500 p-1 text-white opacity-0 transition-opacity hover:bg-danger-600 group-hover:opacity-100"
                        >
                            <x-heroicon-s-x-mark class="h-4 w-4" />
                        </button>
                        
                        {{-- File Name --}}
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-2">
                            <p class="truncate text-xs text-white" x-text="media.name"></p>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- Add Media Button --}}
        <x-filament::button
            type="button"
            color="gray"
            icon="heroicon-o-photo"
            x-on:click="openPicker()"
        >
            <span x-text="selectedMedia && selectedMedia.length > 0 ? (isMultiple ? '{{ __('Add More Media') }}' : '{{ __('Change Media') }}') : '{{ __('Select Media') }}'"></span>
        </x-filament::button>
    </div>
</x-dynamic-component>
