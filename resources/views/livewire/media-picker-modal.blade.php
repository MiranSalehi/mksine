<div
    x-data="{}"
    x-on:open-media-picker.window="
        $wire.open(
            $event.detail.statePath,
            $event.detail.multiple,
            $event.detail.acceptedFileTypes,
            $event.detail.currentSelection
        )
    "
>
    @if($isOpen)
        <div
            class="fixed inset-0 z-[100] overflow-y-auto"
            x-data="{ show: @entangle('isOpen') }"
            x-show="show"
            x-cloak
        >
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm dark:bg-gray-950/80"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-250"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>

            {{-- Modal Container --}}
            <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
                <div
                    class="relative w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200/50 dark:bg-gray-900 dark:ring-white/10"
                    x-show="show"
                    x-transition:enter="transition ease-[cubic-bezier(0.32,0.72,0,1)] duration-350"
                    x-transition:enter-start="opacity-0 scale-[0.96]"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-[cubic-bezier(0.32,0.72,0,1)] duration-250"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-[0.96]"
                >
                    {{-- Header --}}
                    <div class="flex items-center justify-between gap-4 border-b border-gray-200/80 bg-gray-50/80 px-6 py-4 rtl:flex-row-reverse dark:border-gray-700/50 dark:bg-gray-800/50">
                        <div class="flex min-w-0 items-center gap-3 rtl:flex-row-reverse">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-500/20">
                                <x-heroicon-o-photo class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ __('mksine::media_picker.title') }}
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $multiple ? __('mksine::media_picker.select_multiple') : __('mksine::media_picker.select_single') }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="close"
                            class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                        >
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    {{-- Content --}}
                    <div class="max-h-[65vh] overflow-y-auto p-6">
                        {{-- Search & Filters Bar --}}
                        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="relative flex-1">
                                <x-heroicon-o-magnifying-glass class="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="search"
                                    placeholder="{{ __('mksine::media_picker.search_placeholder') }}"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 ps-10 pe-4 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500 dark:focus:border-primary-500 dark:focus:bg-gray-900"
                                >
                            </div>
                            <div class="sm:w-44">
                                <select
                                    wire:model.live="typeFilter"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:focus:border-primary-500 dark:focus:bg-gray-900"
                                >
                                    @foreach($fileTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Upload Zone --}}
                        <div
                            class="mb-6 rounded-2xl border-2 border-dashed border-gray-300 bg-gradient-to-br from-gray-50 to-gray-100/50 p-8 transition-colors dark:border-gray-600 dark:from-gray-800/50 dark:to-gray-800/30"
                            x-data="{ dragged: false }"
                            x-on:dragover.prevent="dragged = true"
                            x-on:dragleave.prevent="dragged = false"
                            x-on:drop.prevent="dragged = false"
                        >
                            <div class="flex flex-col items-center justify-center gap-3 text-center">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/80 shadow-sm dark:bg-gray-700/50">
                                    <x-heroicon-o-cloud-arrow-up class="h-7 w-7 text-gray-400 dark:text-gray-500" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('mksine::media_picker.click_to_upload') }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('mksine::media_picker.browse_files') }}
                                    </p>
                                </div>
                                <input
                                    type="file"
                                    wire:model="uploadedFiles"
                                    multiple
                                    accept="{{ implode(',', $acceptedFileTypes) }}"
                                    class="hidden"
                                    id="media-upload-input-{{ $this->getId() }}"
                                >
                                <button
                                    type="button"
                                    onclick="document.getElementById('media-upload-input-{{ $this->getId() }}').click()"
                                    class="rounded-xl bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600 dark:hover:bg-gray-600"
                                >
                                    {{ __('mksine::media_picker.browse_files') }}
                                </button>
                            </div>

                            @if(count($uploadedFiles) > 0)
                                <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl bg-primary-50 px-4 py-3 rtl:flex-row-reverse dark:bg-primary-500/10">
                                    <span class="text-sm font-medium text-primary-700 dark:text-primary-400">
                                        {{ count($uploadedFiles) }} {{ __('mksine::media_picker.files_ready_to_upload', ['count' => count($uploadedFiles)]) }}
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="uploadFiles"
                                        wire:loading.attr="disabled"
                                        class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <span wire:loading.remove wire:target="uploadFiles">{{ __('mksine::media_picker.upload') }}</span>
                                        <span wire:loading wire:target="uploadFiles" class="inline-flex items-center gap-2">
                                            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ __('mksine::media_picker.uploading') }}
                                        </span>
                                    </button>
                                </div>
                            @endif
                        </div>

                        {{-- Media Grid --}}
                        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6">
                            @forelse($mediaItems as $media)
                                <button
                                    type="button"
                                    wire:click="toggleSelection({{ $media->id }})"
                                    class="group relative aspect-square overflow-hidden rounded-xl border-2 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900
                                        {{ $this->isSelected($media->id)
                                            ? 'border-primary-500 ring-2 ring-primary-500/30'
                                            : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500' }}"
                                >
                                    @if(str_starts_with($media->mime_type, 'image/'))
                                        <img
                                            src="{{ Storage::disk($media->disk)->url($media->path) }}"
                                            alt="{{ $media->name }}"
                                            class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800">
                                            <x-heroicon-o-document class="h-10 w-10 text-gray-400 dark:text-gray-500" />
                                        </div>
                                    @endif

                                    @if($this->isSelected($media->id))
                                        <div class="absolute inset-0 bg-primary-500/20">
                                            <div class="absolute end-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-primary-500 shadow-lg">
                                                <x-heroicon-s-check class="h-4 w-4 text-white" />
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Hover Overlay with filename --}}
                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                        <p class="truncate text-xs font-medium text-white drop-shadow-sm">{{ $media->name }}</p>
                                    </div>
                                </button>
                            @empty
                                <div class="col-span-full flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 py-16 dark:border-gray-700">
                                    <x-heroicon-o-photo class="mx-auto h-14 w-14 text-gray-300 dark:text-gray-600" />
                                    <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">
                                        {{ __('mksine::media_picker.no_media_found') }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                        {{ __('mksine::media_picker.no_media_empty_hint') }}
                                    </p>
                                </div>
                            @endforelse
                        </div>

                        @if($mediaItems->hasPages())
                            <div class="mt-6">
                                {{ $mediaItems->links() }}
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-200/80 bg-gray-50/50 px-6 py-4 rtl:flex-row-reverse dark:border-gray-700/50 dark:bg-gray-800/50">
                        <div class="order-2 rtl:order-1">
                            @if(count($selectedIds) > 0)
                                <span class="inline-flex items-center gap-2 rounded-lg bg-primary-100 px-3 py-1.5 text-sm font-medium text-primary-700 dark:bg-primary-500/20 dark:text-primary-400">
                                    <x-heroicon-s-check-circle class="h-4 w-4" />
                                    {{ count($selectedIds) }} {{ __('mksine::media_picker.items_selected', ['count' => count($selectedIds)]) }}
                                </span>
                            @endif
                        </div>
                        <div class="order-1 flex gap-3 rtl:order-2 rtl:flex-row-reverse">
                            <button
                                type="button"
                                wire:click="close"
                                class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            >
                                {{ __('mksine::media_picker.cancel') }}
                            </button>
                            <button
                                type="button"
                                wire:click="confirm"
                                @if(count($selectedIds) === 0) disabled @endif
                                class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                            >
                                {{ __('mksine::media_picker.confirm_selection') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
