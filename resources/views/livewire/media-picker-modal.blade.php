<div
    x-data="{
        show: @entangle('isOpen').live,
        closingMs: 220,
        openFromEvent(detail) {
            this.show = true;
            $wire.open(
                detail.statePath,
                detail.multiple,
                detail.acceptedFileTypes,
                detail.currentSelection ?? []
            );
        },
        closeAnimated() {
            if (! this.show) {
                return;
            }

            this.show = false;
            window.setTimeout(() => $wire.close(), this.closingMs);
        },
    }"
    x-on:open-media-picker.window="openFromEvent($event.detail)"
    x-on:keydown.escape.window="closeAnimated()"
    x-cloak
>
    <div
        class="mksine-media-picker-root fixed inset-0 z-[100] overflow-y-auto"
        x-show="show"
        style="display: none;"
        role="dialog"
        aria-modal="true"
        :aria-hidden="! show"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm dark:bg-gray-950/80"
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-on:click="closeAnimated()"
        ></div>

        {{-- Modal Container --}}
        <div class="pointer-events-none fixed inset-0 z-10 flex items-center justify-center p-4">
            <div
                class="mksine-media-picker-panel pointer-events-auto relative flex max-h-[min(90vh,920px)] w-full max-w-7xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200/50 dark:bg-gray-900 dark:ring-white/10"
                x-show="show"
                x-transition:enter="ease-[cubic-bezier(0.32,0.72,0,1)] duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-[cubic-bezier(0.32,0.72,0,1)] duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                x-on:click.stop
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
                        x-on:click="closeAnimated()"
                        class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    >
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex min-h-0 flex-1 overflow-hidden">
                    <div class="flex min-h-0 min-w-0 flex-1 flex-col">
                        <div class="shrink-0 space-y-4 border-b border-gray-200/80 p-5 dark:border-gray-700/50">
                    {{-- Search & Filters Bar --}}
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
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

                    {{-- Upload Zone (click + drag-and-drop → Livewire WithFileUploads) --}}
                    @php
                        $mediaUploadInputId = 'media-upload-input-'.$this->getId();
                    @endphp
                    <div
                        class="rounded-xl border border-dashed border-gray-300/80 bg-gray-50/80 p-5 transition-colors dark:border-gray-600/60 dark:bg-gray-800/40"
                        :class="dragged
                            ? 'border-primary-500 from-primary-50/90 to-primary-100/40 ring-2 ring-primary-500/20 dark:border-primary-400 dark:from-primary-500/15 dark:to-primary-500/5 dark:ring-primary-400/20'
                            : 'border-gray-300 dark:border-gray-600'"
                        x-data="{ dragged: false, accepted: @js($acceptedFileTypes) }"
                        x-on:dragenter.prevent="dragged = true"
                        x-on:dragleave.prevent="if (! $event.currentTarget.contains($event.relatedTarget)) dragged = false"
                        x-on:dragover.prevent="$event.dataTransfer.dropEffect = 'copy'; dragged = true"
                        x-on:drop.prevent="
                            dragged = false;
                            const acc = accepted || [];
                            const isOk = (f) => {
                                const t = f.type || '';
                                if (!acc.length) return true;
                                for (const p of acc) {
                                    if (p.endsWith('/*') && t.startsWith(p.slice(0, -1))) return true;
                                    if (p === t) return true;
                                }
                                return false;
                            };
                            const files = Array.from($event.dataTransfer.files || []).filter(isOk);
                            if (files.length) {
                                $wire.uploadMultiple('uploadedFiles', files, () => {}, () => {}, () => {}, () => {}, true);
                            }
                        "
                    >
                        <div class="flex flex-col items-center justify-center gap-2 text-center sm:flex-row sm:gap-4 sm:text-start">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-gray-700/50">
                                <x-heroicon-o-cloud-arrow-up class="h-6 w-6 text-gray-400 dark:text-gray-500" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('mksine::media_picker.click_to_upload') }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('mksine::media_picker.drag_to_upload') }}
                                </p>
                            </div>
                            <input
                                type="file"
                                wire:model="uploadedFiles"
                                multiple
                                accept="{{ implode(',', $acceptedFileTypes) }}"
                                class="hidden"
                                id="{{ $mediaUploadInputId }}"
                            >
                            <button
                                type="button"
                                onclick="document.getElementById('{{ $mediaUploadInputId }}').click()"
                                class="rounded-xl bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-200 transition-all hover:bg-gray-50 hover:shadow dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600 dark:hover:bg-gray-600"
                            >
                                {{ __('mksine::media_picker.browse_files') }}
                            </button>
                        </div>

                        @if(count($uploadedFiles) > 0)
                            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl bg-primary-50 px-4 py-3 rtl:flex-row-reverse dark:bg-primary-500/10">
                                <span class="text-sm font-medium text-primary-700 dark:text-primary-400">
                                    {{ __('mksine::media_picker.files_ready_to_upload', ['count' => count($uploadedFiles)]) }}
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
                        </div>

                        {{-- Media Grid --}}
                        <div class="min-h-0 flex-1 overflow-y-auto p-5">
                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7">
                        @forelse($mediaItems as $media)
                            <button
                                type="button"
                                wire:click="toggleSelection({{ $media->id }})"
                                @class([
                                    'mksine-media-picker-thumb group relative aspect-square overflow-hidden rounded-lg bg-gray-100 transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:bg-gray-800 dark:focus:ring-offset-gray-900',
                                    'is-selected' => $this->isSelected($media->id),
                                    'is-focused' => $this->detailMediaId === $media->id,
                                ])
                            >
                                @if(str_starts_with($media->mime_type, 'image/') && ! str_starts_with($media->mime_type, 'image/svg'))
                                    <img
                                        src="{{ Storage::disk($media->disk)->url($media->path) }}"
                                        alt="{{ $media->name }}"
                                        class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                @elseif(str_starts_with($media->mime_type, 'image/svg'))
                                    <div class="flex h-full w-full items-center justify-center bg-white p-2 dark:bg-gray-800">
                                        <img
                                            src="{{ Storage::disk($media->disk)->url($media->path) }}"
                                            alt="{{ $media->name }}"
                                            class="max-h-full max-w-full object-contain"
                                            loading="lazy"
                                        >
                                    </div>
                                @else
                                    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800">
                                        <x-heroicon-o-document class="h-10 w-10 text-gray-400 dark:text-gray-500" />
                                    </div>
                                @endif

                                @if($this->isSelected($media->id))
                                    <div class="absolute inset-0 bg-primary-500/15">
                                        <div class="absolute end-1.5 top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-primary-500 shadow-md">
                                            <x-heroicon-s-check class="h-3.5 w-3.5 text-white" />
                                        </div>
                                    </div>
                                @elseif($this->detailMediaId === $media->id)
                                    <div class="absolute inset-0 ring-2 ring-inset ring-primary-400/40"></div>
                                @endif
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
                        <div class="mt-4 border-t border-gray-200/80 pt-4 dark:border-gray-700/50">
                            {{ $mediaItems->onEachSide(1)->links('mksine::components.media-picker-pagination') }}
                        </div>
                    @endif

                    @if($detailMedia = $this->detailMedia)
                        <div class="mt-4 rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 lg:hidden dark:bg-gray-800/50 dark:ring-white/10">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $detailMedia->name }}</h4>
                            <dl class="mt-3 grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('mksine::media_picker.file_type') }}</dt>
                                    <dd class="mt-0.5 font-medium text-gray-800 dark:text-gray-200">{{ $detailMedia->mime_type }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('mksine::media_picker.file_size') }}</dt>
                                    <dd class="mt-0.5 font-medium text-gray-800 dark:text-gray-200">{{ $detailMedia->human_size }}</dd>
                                </div>
                                @if($detailMedia->width && $detailMedia->height)
                                    <div class="col-span-2">
                                        <dt class="text-gray-500 dark:text-gray-400">{{ __('mksine::media_picker.dimensions') }}</dt>
                                        <dd class="mt-0.5 font-medium text-gray-800 dark:text-gray-200">
                                            {{ __('mksine::media_picker.dimensions_value', ['width' => number_format($detailMedia->width), 'height' => number_format($detailMedia->height)]) }}
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif
                        </div>
                    </div>

                    {{-- Attachment details (WordPress-style sidebar) --}}
                    <aside class="mksine-media-picker-details hidden w-72 shrink-0 overflow-y-auto lg:block xl:w-80">
                        <div class="px-4 pb-2 pt-4">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ __('mksine::media_picker.attachment_details') }}
                            </h3>
                        </div>

                        @if($detailMedia = $this->detailMedia)
                            <div class="space-y-4 p-4">
                                <div class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                                    @if(str_starts_with($detailMedia->mime_type, 'image/') && ! str_starts_with($detailMedia->mime_type, 'image/svg'))
                                        <img
                                            src="{{ Storage::disk($detailMedia->disk)->url($detailMedia->path) }}"
                                            alt="{{ $detailMedia->name }}"
                                            class="aspect-video w-full object-contain bg-gray-50 dark:bg-gray-900"
                                        >
                                    @elseif(str_starts_with($detailMedia->mime_type, 'image/svg'))
                                        <div class="flex aspect-video items-center justify-center bg-white p-4 dark:bg-gray-900">
                                            <img
                                                src="{{ Storage::disk($detailMedia->disk)->url($detailMedia->path) }}"
                                                alt="{{ $detailMedia->name }}"
                                                class="max-h-full max-w-full object-contain"
                                            >
                                        </div>
                                    @else
                                        <div class="flex aspect-video items-center justify-center bg-gray-100 dark:bg-gray-800">
                                            <x-heroicon-o-document class="h-14 w-14 text-gray-400 dark:text-gray-500" />
                                        </div>
                                    @endif
                                </div>

                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $detailMedia->name }}
                                </p>

                                <dl class="space-y-3 text-sm">
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('mksine::media_picker.file_name') }}
                                        </dt>
                                        <dd class="mt-1 break-all text-gray-800 dark:text-gray-200">
                                            {{ $detailMedia->file_name }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('mksine::media_picker.file_type') }}
                                        </dt>
                                        <dd class="mt-1 text-gray-800 dark:text-gray-200">
                                            {{ $detailMedia->mime_type }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('mksine::media_picker.dimensions') }}
                                        </dt>
                                        <dd class="mt-1 text-gray-800 dark:text-gray-200">
                                            @if($detailMedia->width && $detailMedia->height)
                                                {{ __('mksine::media_picker.dimensions_value', ['width' => number_format($detailMedia->width), 'height' => number_format($detailMedia->height)]) }}
                                            @else
                                                {{ __('mksine::media_picker.dimensions_unknown') }}
                                            @endif
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('mksine::media_picker.file_size') }}
                                        </dt>
                                        <dd class="mt-1 text-gray-800 dark:text-gray-200">
                                            {{ $detailMedia->human_size }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ __('mksine::media_picker.uploaded_on') }}
                                        </dt>
                                        <dd class="mt-1 text-gray-800 dark:text-gray-200">
                                            {{ $detailMedia->created_at?->translatedFormat('Y/m/d H:i') }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        @else
                            <div class="flex min-h-[12rem] items-center justify-center p-6 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('mksine::media_picker.select_for_details') }}
                                </p>
                            </div>
                        @endif
                    </aside>
                </div>

                {{-- Footer --}}
                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-200/80 bg-gray-50/50 px-6 py-4 rtl:flex-row-reverse dark:border-gray-700/50 dark:bg-gray-800/50">
                    <div class="order-2 rtl:order-1">
                        @if(count($selectedIds) > 0)
                            <span class="inline-flex items-center gap-2 rounded-lg bg-primary-100 px-3 py-1.5 text-sm font-medium text-primary-700 dark:bg-primary-500/20 dark:text-primary-400">
                                <x-heroicon-s-check-circle class="h-4 w-4" />
                                {{ __('mksine::media_picker.items_selected', ['count' => count($selectedIds)]) }}
                            </span>
                        @endif
                    </div>
                    <div class="order-1 flex gap-3 rtl:order-2 rtl:flex-row-reverse">
                        <button
                            type="button"
                            x-on:click="closeAnimated()"
                            class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            {{ __('mksine::media_picker.cancel') }}
                        </button>
                        <button
                            type="button"
                            @if(count($selectedIds) === 0) disabled @endif
                            x-on:click="if ($el.disabled) return; show = false; $wire.confirm()"
                            class="rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                        >
                            {{ __('mksine::media_picker.confirm_selection') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
