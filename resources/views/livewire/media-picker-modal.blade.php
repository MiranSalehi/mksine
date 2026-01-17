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
    {{-- Modal Backdrop --}}
    @if($isOpen)
        <div
            class="fixed inset-0 z-50 overflow-y-auto"
            x-data="{ show: @entangle('isOpen') }"
            x-show="show"
            x-cloak
        >
            {{-- Backdrop --}}
            <div 
                class="fixed inset-0 bg-gray-900/50 dark:bg-gray-900/80"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>

            {{-- Modal --}}
            <div class="fixed inset-0 z-10 overflow-y-auto p-4">
                <div 
                    class="mx-auto flex min-h-full max-w-5xl items-center justify-center"
                    x-show="show"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-4"
                >
                    <div class="relative w-full rounded-xl bg-white shadow-xl dark:bg-gray-800">
                        {{-- Header --}}
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ __('Media Library') }}
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $multiple ? __('Select one or more media items') : __('Select a media item') }}
                                </p>
                            </div>
                            <button
                                type="button"
                                wire:click="close"
                                class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700"
                            >
                                <x-heroicon-o-x-mark class="h-5 w-5" />
                            </button>
                        </div>

                        {{-- Content --}}
                        <div class="max-h-[60vh] overflow-y-auto p-6">
                            {{-- Filters --}}
                            <div class="mb-4 flex flex-col gap-4 sm:flex-row">
                                {{-- Search --}}
                                <div class="flex-1">
                                    <input
                                        type="text"
                                        wire:model.live.debounce.300ms="search"
                                        placeholder="{{ __('Search by name...') }}"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                </div>

                                {{-- Type Filter --}}
                                <div class="w-full sm:w-48">
                                    <select
                                        wire:model.live="typeFilter"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        @foreach($fileTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Upload Section --}}
                            <div class="mb-4 rounded-lg border-2 border-dashed border-gray-300 p-4 dark:border-gray-600">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <x-heroicon-o-cloud-arrow-up class="h-8 w-8 text-gray-400" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Click to upload files') }}
                                    </p>
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
                                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        {{ __('Browse Files') }}
                                    </button>
                                </div>

                                @if(count($uploadedFiles) > 0)
                                    <div class="mt-4 flex items-center justify-between rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                                        <span class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ count($uploadedFiles) }} {{ __('file(s) ready to upload') }}
                                        </span>
                                        <button
                                            type="button"
                                            wire:click="uploadFiles"
                                            wire:loading.attr="disabled"
                                            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:opacity-50"
                                        >
                                            <span wire:loading.remove wire:target="uploadFiles">{{ __('Upload') }}</span>
                                            <span wire:loading wire:target="uploadFiles">{{ __('Uploading...') }}</span>
                                        </button>
                                    </div>
                                @endif
                            </div>

                            {{-- Media Grid --}}
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                                @forelse($mediaItems as $media)
                                    <div
                                        wire:click="toggleSelection({{ $media->id }})"
                                        class="group relative aspect-square cursor-pointer overflow-hidden rounded-lg border-2 transition-all
                                            {{ $this->isSelected($media->id) 
                                                ? 'border-primary-500 ring-2 ring-primary-500' 
                                                : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600' 
                                            }}"
                                    >
                                        @if(str_starts_with($media->mime_type, 'image/'))
                                            <img 
                                                src="{{ Storage::disk($media->disk)->url($media->path) }}" 
                                                alt="{{ $media->name }}"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <div class="flex h-full w-full items-center justify-center bg-gray-100 dark:bg-gray-700">
                                                <x-heroicon-o-document class="h-8 w-8 text-gray-400" />
                                            </div>
                                        @endif

                                        {{-- Selection Indicator --}}
                                        @if($this->isSelected($media->id))
                                            <div class="absolute inset-0 bg-primary-500/20">
                                                <div class="absolute right-1 top-1 rounded-full bg-primary-500 p-1">
                                                    <x-heroicon-s-check class="h-3 w-3 text-white" />
                                                </div>
                                            </div>
                                        @endif

                                        {{-- File Info on Hover --}}
                                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-1 opacity-0 transition-opacity group-hover:opacity-100">
                                            <p class="truncate text-xs text-white">{{ $media->name }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-full py-8 text-center">
                                        <x-heroicon-o-photo class="mx-auto h-12 w-12 text-gray-400" />
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('No media found') }}
                                        </p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Pagination --}}
                            @if($mediaItems->hasPages())
                                <div class="mt-4">
                                    {{ $mediaItems->links() }}
                                </div>
                            @endif
                        </div>

                        {{-- Footer --}}
                        <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div>
                                @if(count($selectedIds) > 0)
                                    <p class="text-sm text-primary-600 dark:text-primary-400">
                                        {{ count($selectedIds) }} {{ __('item(s) selected') }}
                                    </p>
                                @endif
                            </div>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    wire:click="close"
                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                >
                                    {{ __('Cancel') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="confirm"
                                    @if(count($selectedIds) === 0) disabled @endif
                                    class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{ __('Confirm Selection') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
