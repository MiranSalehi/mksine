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
        class="space-y-3"
    >
        {{-- Selected Media Preview --}}
        <template x-if="selectedMedia && selectedMedia.length > 0">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                <template x-for="media in selectedMedia" :key="media.id">
                    <div class="group relative aspect-square overflow-hidden rounded-xl border border-gray-200/80 bg-gray-50 shadow-sm ring-1 ring-black/5 transition-all duration-200 hover:shadow-md hover:ring-primary-500/30 dark:border-gray-600/60 dark:bg-gray-800/50 dark:ring-white/5 dark:hover:ring-primary-400/30">
                        <template x-if="media.mime_type && media.mime_type.startsWith('image/')">
                            <img
                                :src="getMediaUrl(media)"
                                :alt="media.name"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            >
                        </template>
                        <template x-if="!media.mime_type || !media.mime_type.startsWith('image/')">
                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800">
                                <x-heroicon-o-document class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                            </div>
                        </template>

                        {{-- Overlay & Remove --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                            <div class="absolute inset-x-0 bottom-0 p-2">
                                <p class="truncate text-xs font-medium text-white drop-shadow-sm" x-text="media.name"></p>
                            </div>
                            <button
                                type="button"
                                x-on:click.stop.prevent="removeMedia(media.id)"
                                class="absolute end-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow-lg backdrop-blur-sm transition-all hover:bg-danger-500 hover:text-white dark:bg-gray-800/90 dark:text-gray-300 dark:hover:bg-danger-500"
                            >
                                <x-heroicon-s-x-mark class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- Add/Select Media Button --}}
        <button
            type="button"
            x-on:click="openPicker()"
            class="inline-flex items-center gap-2 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 px-4 py-3 text-sm font-medium text-gray-600 transition-all duration-200 hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rtl:flex-row-reverse dark:border-gray-600 dark:bg-gray-800/30 dark:text-gray-400 dark:hover:border-primary-500 dark:hover:bg-primary-500/10 dark:hover:text-primary-400"
        >
            <x-heroicon-o-photo class="h-5 w-5 shrink-0" />
            <span x-text="selectedMedia && selectedMedia.length > 0 ? (isMultiple ? '{{ __('mksine::media_picker.add_more_media') }}' : '{{ __('mksine::media_picker.change_media') }}') : '{{ __('mksine::media_picker.select_media') }}'"></span>
        </button>
    </div>
</x-dynamic-component>
