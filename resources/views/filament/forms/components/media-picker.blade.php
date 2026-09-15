<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $statePath = $getStatePath();
        $isMultiple = $getIsMultiple();
        $isReorderable = $getIsReorderable();
        $acceptedFileTypes = $getAcceptedFileTypes();
    @endphp

    <div
        x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            selectedMedia: @js($getSelectedMedia()->toArray()),
            isMultiple: @js($isMultiple),
            isReorderable: @js($isReorderable),
            statePath: @js($statePath),
            acceptedFileTypes: @js($acceptedFileTypes),
            sortable: null,

            init() {
                this.applySelectionOrder(this.state, this.selectedMedia);
                window.addEventListener('media-selected', (event) => {
                    if (event.detail.statePath === this.statePath) {
                        this.applySelectionOrder(event.detail.selectedIds, event.detail.selectedMedia || []);
                    }
                });
                this.$watch('selectedMedia', () => this.$nextTick(() => this.initSortable()));
                this.$nextTick(() => this.initSortable());
            },

            applySelectionOrder(ids, media) {
                const orderedIds = Array.isArray(ids) ? ids.map((id) => parseInt(id, 10)).filter((id) => id > 0) : (ids ? [parseInt(ids, 10)] : []);
                this.state = this.isMultiple ? orderedIds : (orderedIds[0] ? orderedIds : []);
                this.selectedMedia = this.orderSelectedMedia(orderedIds, media || []);
            },

            orderSelectedMedia(ids, media) {
                const map = {};
                (media || []).forEach((item) => { map[item.id] = item; });
                return (ids || []).map((id) => map[id]).filter(Boolean);
            },

            initSortable() {
                if (this.sortable) {
                    this.sortable.destroy();
                    this.sortable = null;
                }
                if (! this.isReorderable || typeof window.Sortable === 'undefined') {
                    return;
                }
                const el = this.$refs.selectedGrid;
                if (! el) {
                    return;
                }
                this.sortable = window.Sortable.create(el, {
                    handle: '[data-reorder-handle]',
                    animation: 150,
                    draggable: '[data-media-id]',
                    onEnd: () => this.syncOrderFromDom(),
                });
            },

            syncOrderFromDom() {
                const el = this.$refs.selectedGrid;
                if (! el) {
                    return;
                }
                const ids = Array.from(el.querySelectorAll('[data-media-id]')).map((node) => parseInt(node.getAttribute('data-media-id'), 10));
                this.applySelectionOrder(ids, this.selectedMedia);
            },

            moveMedia(mediaId, delta) {
                if (! this.isReorderable || ! Array.isArray(this.state)) {
                    return;
                }
                const ids = [...this.state];
                const index = ids.indexOf(mediaId);
                const next = index + delta;
                if (index < 0 || next < 0 || next >= ids.length) {
                    return;
                }
                const swap = ids[index];
                ids[index] = ids[next];
                ids[next] = swap;
                this.applySelectionOrder(ids, this.selectedMedia);
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
                applySelectionOrder($event.detail.selectedIds, $event.detail.selectedMedia || []);
            }
        "
        class="space-y-3"
    >
        <template x-if="selectedMedia && selectedMedia.length > 0">
            <div
                x-ref="selectedGrid"
                class="flex flex-wrap gap-3"
            >
                <template x-for="(media, index) in selectedMedia" :key="media.id">
                    <div
                        :data-media-id="media.id"
                        class="flex w-28 shrink-0 flex-col gap-1"
                    >
                        <div class="group relative aspect-square overflow-hidden rounded-xl border border-gray-200/80 bg-gray-50 shadow-sm ring-1 ring-black/5 transition-all duration-200 hover:shadow-md hover:ring-primary-500/30 dark:border-gray-600/60 dark:bg-gray-800/50 dark:ring-white/5 dark:hover:ring-primary-400/30">
                            <template x-if="media.mime_type && media.mime_type.startsWith('image/')">
                                <img
                                    :src="getMediaUrl(media)"
                                    :alt="media.name"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                >
                            </template>
                            <template x-if="media.mime_type && media.mime_type.startsWith('video/')">
                                <div class="relative h-full w-full bg-black">
                                    <video
                                        :src="getMediaUrl(media)"
                                        class="pointer-events-none h-full w-full object-cover"
                                        muted
                                        preload="metadata"
                                        playsinline
                                    ></video>
                                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/25">
                                        <x-heroicon-s-play class="h-8 w-8 text-white/90" />
                                    </div>
                                </div>
                            </template>
                            <template x-if="media.mime_type && media.mime_type.startsWith('audio/')">
                                <div class="flex h-full w-full flex-col items-center justify-center gap-1 bg-gradient-to-br from-sky-50 to-indigo-100 px-2 dark:from-sky-950 dark:to-indigo-950">
                                    <x-heroicon-o-musical-note class="h-10 w-10 text-sky-500 dark:text-sky-400" />
                                    <p class="max-w-full truncate px-1 text-[10px] font-medium text-sky-800 dark:text-sky-200" x-text="media.name"></p>
                                </div>
                            </template>
                            <template x-if="!media.mime_type || (!media.mime_type.startsWith('image/') && !media.mime_type.startsWith('video/') && !media.mime_type.startsWith('audio/'))">
                                <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800">
                                    <x-heroicon-o-document class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                                </div>
                            </template>

                            @if ($isReorderable)
                                <span
                                    class="pointer-events-none absolute start-1.5 top-1.5 z-10 inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-black/55 px-1 text-[10px] font-semibold text-white"
                                    x-text="index + 1"
                                ></span>
                            @endif

                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                <div class="absolute inset-x-0 bottom-0 p-2">
                                    <p class="truncate text-xs font-medium text-white drop-shadow-sm" x-text="media.name"></p>
                                </div>
                                <button
                                    type="button"
                                    x-on:click.stop.prevent="removeMedia(media.id)"
                                    class="absolute end-1.5 top-1.5 z-20 flex h-6 w-6 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow-md backdrop-blur-sm transition-all hover:bg-danger-500 hover:text-white dark:bg-gray-800/90 dark:text-gray-300 dark:hover:bg-danger-500"
                                >
                                    <x-heroicon-s-x-mark class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        @if ($isReorderable)
                            <div class="flex items-center justify-center gap-0.5 rtl:flex-row-reverse">
                                <button
                                    type="button"
                                    x-on:click.stop.prevent="moveMedia(media.id, -1)"
                                    x-bind:disabled="index === 0"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-800 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                    title="{{ __('mksine::media_picker.move_earlier') }}"
                                    aria-label="{{ __('mksine::media_picker.move_earlier') }}"
                                >
                                    <x-heroicon-o-chevron-left class="h-4 w-4 rtl:hidden" />
                                    <x-heroicon-o-chevron-right class="hidden h-4 w-4 rtl:block" />
                                </button>
                                <button
                                    type="button"
                                    data-reorder-handle
                                    class="flex h-7 w-7 shrink-0 cursor-grab items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-800 active:cursor-grabbing dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                    title="{{ __('mksine::media_picker.reorder_handle') }}"
                                    aria-label="{{ __('mksine::media_picker.reorder_handle') }}"
                                >
                                    <x-heroicon-o-bars-2 class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    x-on:click.stop.prevent="moveMedia(media.id, 1)"
                                    x-bind:disabled="index === selectedMedia.length - 1"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-800 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                    title="{{ __('mksine::media_picker.move_later') }}"
                                    aria-label="{{ __('mksine::media_picker.move_later') }}"
                                >
                                    <x-heroicon-o-chevron-right class="h-4 w-4 rtl:hidden" />
                                    <x-heroicon-o-chevron-left class="hidden h-4 w-4 rtl:block" />
                                </button>
                            </div>
                        @endif
                    </div>
                </template>
            </div>
        </template>

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
