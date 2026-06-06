@php
    $name = $getName();
    $placeholder = $getPlaceholder();
    $height = $getHeight();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $uploadUrl = $getUploadUrl();
    $editorId = str_replace(['.', '[', ']'], ['-', '-', ''], $statePath);
    $editorUiLanguage = $field->getEditorUiLanguage();
    $editorContentLanguage = $field->getEditorContentLanguage();
    $editorContentRtl = $field->isEditorContentRtl();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <x-filament::input.wrapper
        :valid="! $errors->has($statePath)"
    >
        <div wire:ignore class="ckeditor-field-wrapper">
            <style>
                [data-ckeditor-id="{{ $editorId }}"] {
                    --mks-ckeditor-min-height: {{ $height }};
                }
                [data-ckeditor-id="{{ $editorId }}"] .ck-editor__main {
                    min-height: var(--mks-ckeditor-min-height);
                }
                [data-ckeditor-id="{{ $editorId }}"] .ck-editor__editable {
                    min-height: var(--mks-ckeditor-min-height);
                }
            </style>
            <div
                x-data="{
                    state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
                    init() {
                        const key = 'ckeditor-{{ $editorId }}';

                        window.ckeditorInstances = window.ckeditorInstances || {};
                        window.CKEditorFieldConfigs = window.CKEditorFieldConfigs || {};
                        window.CKEditorFieldConfigs['{{ $editorId }}'] = {
                            placeholder: @js($placeholder),
                            height: @js($height),
                            isDisabled: @js($isDisabled),
                            uploadUrl: @js($uploadUrl ?: ''),
                            uiLanguage: @js($editorUiLanguage),
                            contentLanguage: @js($editorContentLanguage),
                            contentRtl: @js($editorContentRtl),
                        };

                        const existingInstance = window.ckeditorInstances[key];
                        if (existingInstance?.instance) {
                            try {
                                if (existingInstance.instance.onKeyDown) {
                                    window.removeEventListener('keydown', existingInstance.instance.onKeyDown, true);
                                }
                                if (existingInstance.mediaSelectedHandler) {
                                    window.removeEventListener('media-selected', existingInstance.mediaSelectedHandler);
                                }
                                existingInstance.instance.destroy();
                            } catch (e) {
                                console.warn('Error destroying old CKEditor instance:', e);
                            }
                        }

                        const instance = window.ckeditorInstances[key] = {
                            instance: null,
                            eventListenerAdded: false,
                            createHandler: null,
                            destroyHandler: null,
                            statePath: null,
                            alpineComponent: null,
                            mediaSelectedHandler: null
                        };

                        const waitFor = (check, cb, maxAttempts = 200) => {
                            let attempts = 0;
                            const t = setInterval(() => {
                                attempts++;
                                if (check() || attempts >= maxAttempts) {
                                    clearInterval(t);
                                    cb();
                                }
                            }, 50);
                        };

                        instance.createHandler = () => waitFor(
                            () => typeof window.createCKEditor === 'function' && typeof window.ClassicEditor !== 'undefined',
                            () => window.createCKEditor('{{ $editorId }}', '{{ $statePath }}', this)
                        );

                        instance.destroyHandler = () => waitFor(
                            () => typeof window.destroyCKEditor === 'function',
                            () => window.destroyCKEditor('{{ $editorId }}')
                        );

                        document.addEventListener('livewire:navigated', instance.createHandler);
                        document.addEventListener('livewire:navigate', instance.destroyHandler);

                        this.$nextTick(() => instance.createHandler());

                        this.$watch('state', (value) => {
                            const editor = instance.instance;
                            if (!editor) return;
                            if (instance.__fromEditor) return;
                            if (value !== null && value !== undefined) {
                                const currentContent = editor.getData();
                                if (currentContent !== value) editor.setData(value);
                            }
                        });

                        const cleanup = () => {
                            const inst = window.ckeditorInstances[key];
                            if (inst) {
                                if (inst.createHandler) document.removeEventListener('livewire:navigated', inst.createHandler);
                                if (inst.destroyHandler) document.removeEventListener('livewire:navigate', inst.destroyHandler);
                                if (inst.instance) {
                                    try {
                                        if (inst.instance.onKeyDown) window.removeEventListener('keydown', inst.instance.onKeyDown, true);
                                        if (inst.mediaSelectedHandler) window.removeEventListener('media-selected', inst.mediaSelectedHandler);
                                        inst.instance.destroy();
                                    } catch (e) { console.warn('Error destroying CKEditor on cleanup:', e); }
                                }
                                window.ckeditorInstances[key] = null;
                            }
                        };

                        if (typeof this.$cleanup === 'function') {
                            this.$cleanup(cleanup);
                        } else {
                            this.$el.__x_cleanup = cleanup;
                        }
                    },
                    destroy() {
                        if (this.$el.__x_cleanup) this.$el.__x_cleanup();
                    }
                }"
                x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('mks-ckeditor-field', package: 'miran/mksine'))]"
                x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('mks-ckeditor-field', package: 'miran/mksine')), @js(\Filament\Support\Facades\FilamentAsset::getStyleHref('mks-ckeditor-filament', package: 'miran/mksine'))]"
                data-ckeditor-id="{{ $editorId }}"
                style="min-height: var(--mks-ckeditor-min-height, {{ $height }}); resize: vertical; overflow: auto;"
            >
                <textarea
                    id="ckeditor-{{ $editorId }}"
                    name="{{ $name }}"
                    dir="{{ $editorContentRtl ? 'rtl' : 'ltr' }}"
                    lang="{{ $editorContentLanguage }}"
                    x-model="state"
                ></textarea>
            </div>
        </div>
    </x-filament::input.wrapper>
</x-dynamic-component>
