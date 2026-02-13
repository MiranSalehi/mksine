<div class="h-full min-h-[calc(100vh-6rem)]"
     id="mksine-page-builder-root"
     x-data="{
         fullScreen: false,
         pasteBoxOpen: false,
         pastePosition: null,
         pasteText: '',
         openPreview() {
             const previewUrl = $wire.get('previewUrl') || '';
             if (!previewUrl) { console.error('mksine: Preview URL not set'); return; }
             const blocks = $wire.blocks;
             const form = document.createElement('form');
             form.method = 'POST';
             form.action = previewUrl;
             form.target = '_blank';
             const token = document.querySelector('meta[name=csrf-token]')?.content || '';
             form.innerHTML = '<input type=hidden name=_token value=' + token + '><input type=hidden name=blocks value=\'' + JSON.stringify(blocks).replace(/'/g, String.fromCharCode(38,35,51,57)) + '\'><input type=hidden name=title value=Preview>';
             document.body.appendChild(form);
             form.submit();
             form.remove();
         },
         copyToClipboard(detail) {
             const str = detail?.data || (typeof detail === 'string' ? detail : '');
             if (!str) return;
             if (navigator.clipboard?.writeText) {
                 navigator.clipboard.writeText(str).catch(() => {
                     const ta = document.createElement('textarea');
                     ta.value = str;
                     document.body.appendChild(ta);
                     ta.select();
                     document.execCommand('copy');
                     ta.remove();
                 });
             } else {
                 const ta = document.createElement('textarea');
                 ta.value = str;
                 document.body.appendChild(ta);
                 ta.select();
                 document.execCommand('copy');
                 ta.remove();
             }
         },
         openPasteBox(position) { pasteBoxOpen = true; pastePosition = position ?? null; pasteText = ''; $nextTick(() => { $refs.pasteTextarea?.focus(); }); },
         submitPaste() { if (pasteText.trim()) { $wire.pasteBlock(pasteText.trim(), pastePosition); pasteBoxOpen = false; pasteText = ''; pastePosition = null; } }
     }"
     x-init="window.__mksineOpenPasteBox = (pos) => openPasteBox(pos)"
     x-bind:class="fullScreen ? 'fixed inset-0 z-50 bg-white dark:bg-gray-900' : ''"
     @keydown.escape.window="fullScreen = false; pasteBoxOpen = false"
     @modal-closed.window="if ($event.detail?.id === 'template-picker-modal') $wire.closeTemplatePanel(); if ($event.detail?.id === 'block-editor-modal') $wire.closeEditor()"
     @copy-to-clipboard.window="copyToClipboard($event.detail)"
     @pagebuilder:show-paste-box.window="openPasteBox($event.detail?.position)">
    <div class="mksine-page-builder h-full flex flex-col min-h-0"
         wire:ignore.self>
    <style>
        [x-cloak] { display: none !important; }
        .sortable-ghost { opacity: 0.4; }
        .sortable-chosen { box-shadow: 0 10px 40px -10px rgba(0,0,0,0.3); }
        .sortable-drag { opacity: 1; }
    </style>

    {{-- Full-width row: main area + right sidebar --}}
    <div class="flex-1 flex min-h-0">
        {{-- Main area: toolbar + template panel + canvas --}}
        <div class="flex-1 flex flex-col min-w-0 min-h-0">
            {{-- Toolbar --}}
            <div class="flex-shrink-0 flex items-center justify-between py-3 px-1 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-pink-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <x-heroicon-o-squares-2x2 class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">{{ __('Page Builder') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($blocks) }} {{ __('components') }}</p>
                    </div>
                    @if(!$showComponentPanel)
                    <button
                        type="button"
                        wire:click="toggleTemplatePanel"
                        class="group inline-flex items-center gap-2.5 px-5 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-purple-600 via-purple-500 to-pink-500 rounded-xl hover:shadow-xl hover:scale-105 transition-all duration-300 shadow-lg ring-2 ring-purple-400/30"
                        title="{{ __('Start from a pre-designed template') }}"
                    >
                        <div class="relative">
                            <x-heroicon-o-sparkles class="w-5 h-5 animate-pulse" />
                            <span class="absolute -top-1 -right-1 flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                            </span>
                        </div>
                        <span class="bg-gradient-to-r from-white to-purple-100 bg-clip-text text-transparent font-extrabold">
                            {{ __('Use Template') }}
                        </span>
                        <x-heroicon-s-chevron-down class="w-4 h-4 group-hover:translate-y-0.5 transition-transform" />
                    </button>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-0.5 border-r border-gray-300 dark:border-gray-600 pr-2 mr-1">
                        <button
                            type="button"
                            wire:click="undo"
                            @if(!$this->canUndo()) disabled @endif
                            class="p-2 rounded-lg transition-colors {{ $this->canUndo() ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer' : 'opacity-50 cursor-not-allowed text-gray-400 dark:text-gray-600' }}"
                            title="{{ __('Undo (Cmd+Z)') }}"
                        >
                            <x-heroicon-o-arrow-uturn-left class="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            wire:click="redo"
                            @if(!$this->canRedo()) disabled @endif
                            class="p-2 rounded-lg transition-colors {{ $this->canRedo() ? 'text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer' : 'opacity-50 cursor-not-allowed text-gray-400 dark:text-gray-600' }}"
                            title="{{ __('Redo (Cmd+Shift+Z)') }}"
                        >
                            <x-heroicon-o-arrow-uturn-right class="w-4 h-4" />
                        </button>
                    </div>
                    <button
                        type="button"
                        wire:click="openComponentPanel"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium {{ $showComponentPanel ? 'text-white bg-gradient-to-r from-pink-500 to-purple-600' : 'text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg transition-colors"
                    >
                        <x-heroicon-o-plus class="w-4 h-4" />
                        {{ __('Add Component') }}
                    </button>
                    <button
                        type="button"
                        @click="fullScreen = !fullScreen"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        :title="fullScreen ? '{{ __('Exit full screen') }}' : '{{ __('Full screen') }}'"
                    >
                        <span x-show="!fullScreen" class="inline-flex"><x-heroicon-o-arrows-pointing-out class="w-4 h-4" /></span>
                        <span x-show="fullScreen" class="inline-flex" x-cloak><x-heroicon-o-arrows-pointing-in class="w-4 h-4" /></span>
                        <span x-text="fullScreen ? '{{ __('Exit full screen') }}' : '{{ __('Full screen') }}'"></span>
                    </button>
                    @if(!$showComponentPanel)
                    <button
                        type="button"
                        @click="openPreview()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-pink-500 to-purple-600 rounded-lg hover:from-pink-600 hover:to-purple-700 transition-all"
                    >
                        <x-heroicon-o-eye class="w-4 h-4" />
                        {{ __('Preview') }}
                    </button>
                    @endif
                </div>
            </div>

            {{-- Scrollable canvas: blocks list (isolate stacking so insertion z-10 stays local) --}}
            <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden px-1 py-2 relative z-0">
    {{-- Blocks List with Insertion Points --}}
    <div id="blocks-list" class="space-y-2">
        @foreach($blocks as $index => $block)
            @if($index === 0)
                <div class="insertion-point group relative min-h-[10px] py-0.5">
                    <div class="absolute inset-0 flex items-center justify-center gap-2 z-10 opacity-0 group-hover:opacity-100 transition-[opacity] duration-150 ease-out will-change-[opacity]">
                        <button
                            type="button"
                            wire:click="addBlockAtPosition(0)"
                            class="insertion-btn group/btn inline-flex items-center gap-2.5 rounded-full border-2 border-dashed border-gray-300 dark:border-gray-600 bg-white/95 dark:bg-gray-800/95 px-5 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 shadow-lg shadow-gray-200/40 dark:shadow-none backdrop-blur-sm transition-[border-color,background-color,color,box-shadow,transform] duration-150 ease-out hover:border-purple-400 dark:hover:border-purple-500 hover:bg-purple-50/90 dark:hover:bg-purple-900/30 hover:text-purple-700 dark:hover:text-purple-300 hover:shadow-xl hover:shadow-purple-500/10 hover:scale-[1.02] active:scale-[0.98]"
                        >
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 transition-[background-color] duration-150 ease-out group-hover/btn:bg-purple-100 dark:group-hover/btn:bg-purple-800/50">
                                <x-heroicon-o-plus class="w-4 h-4" />
                            </span>
                            <span>{{ __('Add component here') }}</span>
                        </button>
                        <button
                            type="button"
                            wire:click="pasteFromClipboard(0)"
                            class="insertion-btn inline-flex items-center gap-2 rounded-full border border-gray-300 dark:border-gray-600 bg-white/90 dark:bg-gray-800/90 px-3 py-2 text-xs font-medium text-gray-600 dark:text-gray-400 shadow hover:bg-gray-50 dark:hover:bg-gray-700/80 hover:border-purple-300 dark:hover:border-purple-600 transition-colors duration-150 ease-out"
                            title="{{ __('Paste (Cmd+V)') }}"
                        >
                            <x-heroicon-o-clipboard-document class="w-4 h-4" />
                            <span>{{ __('Paste') }}</span>
                        </button>
                    </div>
                    <div class="h-px border-t-2 border-dashed border-gray-200 dark:border-gray-700 group-hover:border-purple-300 dark:group-hover:border-purple-600/70 transition-[border-color] duration-150 ease-out"></div>
                </div>
            @endif
            @include('mksine::page-builder.partials.block-item', ['block' => $block, 'index' => $index, 'parentId' => null, 'columnIndex' => null])
            <div class="insertion-point group relative min-h-[10px] py-0.5">
                <div class="absolute inset-0 flex items-center justify-center gap-2 z-10 opacity-0 group-hover:opacity-100 transition-[opacity] duration-150 ease-out will-change-[opacity]">
                    <button
                        type="button"
                        wire:click="addBlockAtPosition({{ $index + 1 }})"
                        class="insertion-btn group/btn inline-flex items-center gap-2.5 rounded-full border-2 border-dashed border-gray-300 dark:border-gray-600 bg-white/95 dark:bg-gray-800/95 px-5 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 shadow-lg shadow-gray-200/40 dark:shadow-none backdrop-blur-sm transition-[border-color,background-color,color,box-shadow,transform] duration-150 ease-out hover:border-purple-400 dark:hover:border-purple-500 hover:bg-purple-50/90 dark:hover:bg-purple-900/30 hover:text-purple-700 dark:hover:text-purple-300 hover:shadow-xl hover:shadow-purple-500/10 hover:scale-[1.02] active:scale-[0.98]"
                    >
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 transition-[background-color] duration-150 ease-out group-hover/btn:bg-purple-100 dark:group-hover/btn:bg-purple-800/50">
                            <x-heroicon-o-plus class="w-4 h-4" />
                        </span>
                        <span>{{ __('Add component here') }}</span>
                    </button>
                    <button
                        type="button"
                        wire:click="pasteFromClipboard({{ $index + 1 }})"
                        class="insertion-btn inline-flex items-center gap-2 rounded-full border border-gray-300 dark:border-gray-600 bg-white/90 dark:bg-gray-800/90 px-3 py-2 text-xs font-medium text-gray-600 dark:text-gray-400 shadow hover:bg-gray-50 dark:hover:bg-gray-700/80 hover:border-purple-300 dark:hover:border-purple-600 transition-colors duration-150 ease-out"
                        title="{{ __('Paste (Cmd+V)') }}"
                    >
                        <x-heroicon-o-clipboard-document class="w-4 h-4" />
                        <span>{{ __('Paste') }}</span>
                    </button>
                </div>
                <div class="h-px border-t-2 border-dashed border-gray-200 dark:border-gray-700 group-hover:border-purple-300 dark:group-hover:border-purple-600/70 transition-[border-color] duration-150 ease-out"></div>
            </div>
        @endforeach
        @if(empty($blocks))
            <div class="relative overflow-hidden text-center py-20 px-8 rounded-2xl border-2 border-dashed border-purple-200 dark:border-purple-800/60 bg-gradient-to-br from-gray-50 via-purple-50/30 to-pink-50/20 dark:from-gray-800/80 dark:via-purple-900/10 dark:to-pink-900/10 ring-1 ring-purple-100/50 dark:ring-purple-900/30">
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.07] dark:opacity-[0.05]">
                    <x-heroicon-o-squares-2x2 class="w-64 h-64 text-purple-600" />
                </div>
                <div class="relative">
                    <div class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-2xl bg-gradient-to-br from-purple-500 via-purple-600 to-pink-500 shadow-lg shadow-purple-500/25 ring-4 ring-purple-400/20 dark:ring-purple-500/30">
                        <x-heroicon-o-document-plus class="w-8 h-8 text-white" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2 tracking-tight">{{ __('Your page is empty') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-sm mx-auto text-sm leading-relaxed">{{ __('Start building by adding your first component') }}</p>
                    <button
                        type="button"
                        wire:click="addBlockAtPosition(0)"
                        class="inline-flex items-center gap-2.5 px-6 py-3.5 text-sm font-bold text-white bg-gradient-to-r from-purple-600 via-purple-500 to-pink-500 rounded-xl hover:from-purple-700 hover:via-purple-600 hover:to-pink-600 shadow-lg shadow-purple-500/30 hover:shadow-xl hover:shadow-purple-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 ring-2 ring-purple-400/30"
                    >
                        <x-heroicon-o-plus class="w-5 h-5" />
                        {{ __('Add Component') }}
                    </button>
                </div>
            </div>
        @endif
    </div>
            </div>
        </div>

        {{-- Right sidebar: components (grouped), animated open/close --}}
        @php
            $categoryMeta = $this->categoryMeta ?? [];
            $orderKey = collect($categoryMeta)->mapWithKeys(fn ($m, $k) => [$k => $m['order'] ?? 99])->all();
            $sortedCategories = collect($this->components ?? [])->keys()->sortBy(fn ($c) => $orderKey[$c] ?? 99)->values()->all();
        @endphp
        <div class="flex-shrink-0 overflow-hidden transition-[width] duration-300 ease-out"
             x-data="{ open: @entangle('showComponentPanel') }"
             :class="open ? 'w-80' : 'w-0'">
            <aside class="w-80 h-full flex flex-col min-h-0 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/80 shadow-sm dark:shadow-none overflow-hidden">
                <div class="flex-shrink-0 flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h4 class="font-semibold text-gray-800 dark:text-gray-200">{{ __('Components') }}</h4>
                    <button type="button" @click="open = false" wire:click="closeComponentPanel" class="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" title="{{ __('Close') }}">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-3 space-y-6">
                    @foreach($sortedCategories as $category)
                        @php $meta = $categoryMeta[$category] ?? ['name' => $category, 'icon' => 'heroicon-o-square-2-stack']; @endphp
                        <section>
                            <div class="flex items-center gap-2 mb-2">
                                @if(isset($meta['icon']))
                                    <x-dynamic-component :component="$meta['icon']" class="w-4 h-4 text-purple-500 dark:text-purple-400 shrink-0" />
                                @endif
                                <h5 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $meta['name'] ?? $category }}</h5>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($this->components[$category] ?? [] as $info)
                                    <button
                                        type="button"
                                        wire:click="addBlock('{{ $info['type'] }}', {{ $insertAtPosition ?? 'null' }}, {{ $insertInParent ? "'{$insertInParent}'" : 'null' }}, {{ $insertInColumn !== null ? $insertInColumn : 'null' }})"
                                        class="flex flex-col items-center gap-1.5 p-2.5 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-pink-400 dark:hover:border-pink-500 hover:shadow-md hover:shadow-pink-500/5 transition-all duration-200 text-center min-w-0"
                                    >
                                        <x-dynamic-component :component="$info['icon']" class="w-6 h-6 text-pink-500 dark:text-pink-400 shrink-0" />
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate w-full">{{ $info['name'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>

    {{-- Modals layer: above canvas so they never sit under "Add component here" --}}
    <div class="relative z-50">
    {{-- Block Editor Modal --}}
    @php
        $editorHeading = __('Edit Component');
        if ($editingBlockId && !empty($editingBlockData['block']['type'])) {
            $registry = app(\Miran\Mksine\Core\PageBuilder\ComponentRegistry::class);
            $compClass = $registry->get($editingBlockData['block']['type']);
            $editorHeading = $compClass ? $compClass::getName() : $editorHeading;
        }
    @endphp
    <x-filament::modal id="block-editor-modal" :heading="$editorHeading" width="2xl">
        @if($editingBlockId)
            <div wire:key="block-editor-wrap-{{ $editingBlockId }}">
                @livewire('mksine::component-editor', [
                    'blockId' => $editingBlockId,
                    'blockType' => $editingBlockData['block']['type'] ?? '',
                    'blockData' => $editingBlockData['block']['data'] ?? [],
                    'parentId' => $editingBlockData['parentId'] ?? null,
                    'columnIndex' => $editingBlockData['columnIndex'] ?? null,
                ], 'block-editor-'.$editingBlockId)
            </div>
        @endif
    </x-filament::modal>

    @php
        $templateRegistry = app(\Miran\Mksine\Core\PageBuilder\TemplateRegistry::class);
        $templatesByCategory = $templateRegistry->byCategory();
    @endphp
    <x-filament::modal id="template-picker-modal" heading="{{ __('Choose a template') }}" width="4xl">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Start from a pre-built layout') }}</p>
        <div class="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-300 bg-amber-50/80 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 mb-4">
            <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0" />
            <span>{{ __('Loading a template will replace your current content. Save any changes first.') }}</span>
        </div>
        <div class="max-h-[60vh] overflow-y-auto">
            <div class="space-y-6">
                @foreach($templatesByCategory as $category => $templates)
                    <section>
                        @if(is_string($category) && $category !== '')
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 px-1">{{ $category }}</p>
                        @endif
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($templates as $key => $template)
                                <button
                                    type="button"
                                    wire:click="loadTemplate('{{ $key }}')"
                                    class="group relative flex flex-col items-start text-left p-4 rounded-xl bg-gray-50/80 dark:bg-gray-800/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 hover:border-purple-300 dark:hover:border-purple-600 hover:shadow-lg hover:shadow-purple-500/10 dark:hover:shadow-purple-500/5 transition-all duration-200 hover:-translate-y-0.5  dark:focus:ring-offset-gray-900"
                                >
                                    <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-white dark:bg-gray-700/80 shadow-sm group-hover:bg-gradient-to-br group-hover:from-purple-500 group-hover:to-pink-500 group-hover:shadow-md group-hover:shadow-purple-500/20 transition-all duration-200 mb-3">
                                        <x-heroicon-o-squares-2x2 class="w-4 h-4 text-gray-500 dark:text-gray-400 group-hover:text-white transition-colors" />
                                    </span>
                                    <span class="font-semibold text-gray-900 dark:text-white text-sm">{{ $template['name'] ?? $key }}</span>
                                    @if(!empty($template['description']))
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $template['description'] }}</p>
                                    @endif
                                    <span class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-purple-600 dark:text-purple-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                        {{ __('Use template') }}
                                        <x-heroicon-o-arrow-right class="w-3.5 h-3.5" />
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
        <x-slot:footer>
            <x-filament::button color="gray" wire:click="closeTemplatePanel">
                {{ __('Cancel') }}
            </x-filament::button>
        </x-slot:footer>
    </x-filament::modal>

    </div>
    {{-- /Modals layer --}}

    {{-- Paste box: وقتی Clipboard API نیست، کاربر اینجا Cmd+V میزنه و Insert --}}
    <template x-teleport="body">
        <div x-show="pasteBoxOpen"
             x-cloak
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50"
             @keydown.escape.window="pasteBoxOpen = false">
            <div x-show="pasteBoxOpen"
                 x-transition
                 @click.outside="pasteBoxOpen = false"
                 class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-4 border border-gray-200 dark:border-gray-700">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Paste component') }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ __('Clipboard access is not available here. Paste the copied JSON below (Ctrl+V / Cmd+V) and click Insert.') }}</p>
                <textarea x-ref="pasteTextarea"
                          x-model="pasteText"
                          rows="6"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-sm font-mono p-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                          placeholder='{"id":"...","type":"...","data":{...}}'></textarea>
                <div class="flex justify-end gap-2 mt-3">
                    <button type="button" @click="pasteBoxOpen = false; pasteText = ''" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" @click="submitPaste()" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700">
                        {{ __('Insert') }}
                    </button>
                </div>
            </div>
        </div>
    </template>

    </div>
</div>

@script
<script>
(function() {
    function handleCopyToClipboard(event) {
        const payload = event?.detail ?? event?.data ?? event;
        const str = typeof payload === 'object' && payload?.data != null ? payload.data : (typeof payload === 'string' ? payload : JSON.stringify(payload));
        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(str).catch(() => {
                const ta = document.createElement('textarea');
                ta.value = str;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                ta.remove();
            });
        } else {
            const ta = document.createElement('textarea');
            ta.value = str;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
        }
    }
    document.addEventListener('copy-to-clipboard', handleCopyToClipboard);
    if (typeof Livewire !== 'undefined') {
        Livewire.on('copy-to-clipboard', handleCopyToClipboard);
    }

    function openPasteBoxVanilla(position) {
        const list = document.getElementById('blocks-list');
        const wireEl = list?.closest('[wire\\:id]');
        const wireId = wireEl?.getAttribute('wire:id');
        if (!wireId || typeof Livewire === 'undefined') return;
        const overlay = document.createElement('div');
        overlay.className = 'mksine-paste-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;padding:1rem;';
        const box = document.createElement('div');
        box.className = 'mksine-paste-box';
        box.style.cssText = 'background:var(--filament-panel-bg, #fff);color:var(--filament-panel-text, #111);border-radius:0.75rem;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);max-width:32rem;width:100%;padding:1.25rem;border:1px solid rgba(0,0,0,0.1);';
        box.innerHTML = '<h4 style="font-weight:600;margin:0 0 0.5rem 0;font-size:1rem;">Paste component</h4><p style="font-size:0.875rem;color:#6b7280;margin:0 0 0.75rem 0;">Paste the copied JSON below (Ctrl+V / Cmd+V) and click Insert.</p><textarea rows="6" placeholder=\'{"id":"...","type":"...","data":{...}}\' style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.75rem;font-family:monospace;font-size:0.8125rem;resize:vertical;background:#f9fafb;color:#111;margin-bottom:1rem;"></textarea><div style="display:flex;justify-content:flex-end;gap:0.5rem;"><button type="button" data-action="cancel" style="padding:0.5rem 1rem;font-size:0.875rem;border-radius:0.5rem;border:1px solid #d1d5db;background:#f3f4f6;cursor:pointer;">Cancel</button><button type="button" data-action="insert" style="padding:0.5rem 1rem;font-size:0.875rem;border-radius:0.5rem;border:none;background:#9333ea;color:#fff;cursor:pointer;">Insert</button></div>';
        const textarea = box.querySelector('textarea');
        const close = () => { overlay.remove(); document.removeEventListener('keydown', onEsc); };
        const onEsc = (e) => { if (e.key === 'Escape') close(); };
        overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
        box.querySelector('[data-action="cancel"]').addEventListener('click', close);
        box.querySelector('[data-action="insert"]').addEventListener('click', () => {
            const text = textarea.value.trim();
            if (text) { Livewire.find(wireId).call('pasteBlock', text, position); }
            close();
        });
        document.addEventListener('keydown', onEsc);
        overlay.appendChild(box);
        document.body.appendChild(overlay);
        setTimeout(() => textarea.focus(), 50);
    }

    let lastRequestPasteAt = 0;
    async function handleRequestPaste(event) {
        if (Date.now() - lastRequestPasteAt < 400) return;
        lastRequestPasteAt = Date.now();
        const payload = event?.detail ?? event;
        let position = null;
        if (payload != null) {
            if (Array.isArray(payload)) position = payload[0]?.position ?? payload[0];
            else position = payload.position;
        }
        if (!navigator.clipboard || typeof navigator.clipboard.readText !== 'function') {
            openPasteBoxVanilla(position);
            return;
        }
        let text = '';
        try { text = await navigator.clipboard.readText(); } catch (err) { openPasteBoxVanilla(position); return; }
        if (!text?.trim()) return;
        const list = document.getElementById('blocks-list');
        const wireEl = list?.closest('[wire\\:id]');
        const wireId = wireEl?.getAttribute('wire:id');
        if (wireId && typeof Livewire !== 'undefined') {
            try { Livewire.find(wireId).call('pasteBlock', text, position); } catch (err) {}
        }
    }
    document.addEventListener('request-paste', handleRequestPaste);
    if (typeof Livewire !== 'undefined') Livewire.on('request-paste', handleRequestPaste);

    document.addEventListener('keydown', (e) => {
        if (document.activeElement?.tagName === 'INPUT' || document.activeElement?.tagName === 'TEXTAREA' || document.activeElement?.isContentEditable) return;
        if ((e.metaKey || e.ctrlKey) && e.key === 'z' && !e.shiftKey) { e.preventDefault(); @this.call('undo'); }
        if ((e.metaKey || e.ctrlKey) && e.key === 'z' && e.shiftKey) { e.preventDefault(); @this.call('redo'); }
        if ((e.metaKey || e.ctrlKey) && e.key === 'y') { e.preventDefault(); @this.call('redo'); }
        if ((e.metaKey || e.ctrlKey) && e.key === 'v') {
            e.preventDefault();
            const list = document.getElementById('blocks-list');
            const wireId = list?.closest('[wire\\:id]')?.getAttribute('wire:id');
            if (wireId && typeof Livewire !== 'undefined') {
                if (navigator.clipboard && typeof navigator.clipboard.readText === 'function') {
                    navigator.clipboard.readText().then((text) => {
                        if (text?.trim()) Livewire.find(wireId).call('pasteBlock', text, null);
                    }).catch(() => { openPasteBoxVanilla(null); });
                } else {
                    openPasteBoxVanilla(null);
                }
            }
        }
    });
})();
</script>
@endscript
