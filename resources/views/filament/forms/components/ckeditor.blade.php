@php
    $name = $getName();
    $placeholder = $getPlaceholder();
    $isConcealed = $isConcealed();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    // Create a safe identifier from statePath for use in JavaScript
    $editorId = str_replace(['.', '[', ']'], ['-', '-', ''], $statePath);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <x-filament::input.wrapper
        :valid="! $errors->has($statePath)"
    >
        <div wire:ignore>
            <script type="text/javascript">
                // Initialize the instance and event listener flags if not already set
                if (!window.ckeditorInstances["ckeditor-{{ $editorId }}"]) {
                    window.ckeditorInstances["ckeditor-{{ $editorId }}"] = {
                        instance: null,
                        eventListenerAdded: false,
                        createHandler: null,
                        destroyHandler: null
                    };
                }

                window.createCKEditor = function(editorId, statePath, alpineComponent) {
                    // To prevent duplicates, halt here if an editor already exists
                    if (window.ckeditorInstances["ckeditor-" + editorId].instance) {
                        return;
                    }

                    // Check if the textarea element exists
                    const textarea = document.querySelector('#ckeditor-' + editorId);
                    if (!textarea) {
                        return;
                    }

                    // Store statePath and Alpine component for this editor instance
                    window.ckeditorInstances["ckeditor-" + editorId].statePath = statePath;
                    window.ckeditorInstances["ckeditor-" + editorId].alpineComponent = alpineComponent;

                    // Store editorId for use in componentFactory
                    const currentEditorIdForMedia = editorId;

                    // Create a Plugin class for insertMedia button
                    // We need to extend from Plugin base class
                    // Get Plugin class from Essentials
                    const PluginBase = Object.getPrototypeOf(Essentials.prototype).constructor;
                    
                    class InsertMediaPlugin extends PluginBase {
                        static get pluginName() {
                            return 'InsertMedia';
                        }

                        init() {
                            const editor = this.editor;
                            const mediaPickerStatePath = 'ckeditor-media-' + currentEditorIdForMedia;
                            
                            // Get ButtonView class from an existing button
                            const existingButton = editor.ui.componentFactory.create('bold');
                            const ButtonViewClass = existingButton.constructor;
                            
                            editor.ui.componentFactory.add('insertMedia', locale => {
                                const view = new ButtonViewClass(locale);
                                
                                view.set({
                                    label: 'Insert Media',
                                    icon: ` <svg xmlns="http://www.w3.org/2000/svg" fill="#000000" width="20px" height="20px" viewBox="0 0 24 24"><path d="M19,13a1,1,0,0,0-1,1v.38L16.52,12.9a2.79,2.79,0,0,0-3.93,0l-.7.7L9.41,11.12a2.85,2.85,0,0,0-3.93,0L4,12.6V7A1,1,0,0,1,5,6h7a1,1,0,0,0,0-2H5A3,3,0,0,0,2,7V19a3,3,0,0,0,3,3H17a3,3,0,0,0,3-3V14A1,1,0,0,0,19,13ZM5,20a1,1,0,0,1-1-1V15.43l2.9-2.9a.79.79,0,0,1,1.09,0l3.17,3.17,0,0L15.46,20Zm13-1a.89.89,0,0,1-.18.53L13.31,15l.7-.7a.77.77,0,0,1,1.1,0L18,17.21ZM22.71,4.29l-3-3a1,1,0,0,0-.33-.21,1,1,0,0,0-.76,0,1,1,0,0,0-.33.21l-3,3a1,1,0,0,0,1.42,1.42L18,4.41V10a1,1,0,0,0,2,0V4.41l1.29,1.3a1,1,0,0,0,1.42,0A1,1,0,0,0,22.71,4.29Z"/></svg>`,
                                    tooltip: true
                                });

                                view.on('execute', () => {
                                    // Dispatch event to open media picker
                                    window.dispatchEvent(new CustomEvent('open-media-picker', {
                                        detail: {
                                            statePath: mediaPickerStatePath,
                                            multiple: true,
                                            acceptedFileTypes: ['image/*'],
                                            currentSelection: []
                                        }
                                    }));
                                });

                                return view;
                            });
                        }
                    }

                    // Create new editor instance
                    ClassicEditor
                        .create(textarea, {
                            plugins: [
                                AccessibilityHelp,
                                Alignment,
                                Autoformat,
                                AutoImage,
                                AutoLink,
                                Autosave,
                                BlockQuote,
                                Bold,
                                Code,
                                CodeBlock,
                                Essentials,
                                FindAndReplace,
                                FontBackgroundColor,
                                FontColor,
                                FontFamily,
                                FontSize,
                                GeneralHtmlSupport,
                                Heading,
                                Highlight,
                                HorizontalLine,
                                HtmlComment,
                                HtmlEmbed,
                                ImageBlock,
                                ImageCaption,
                                ImageInline,
                                ImageResize,
                                ImageStyle,
                                ImageTextAlternative,
                                ImageToolbar,
                                Indent,
                                IndentBlock,
                                Italic,
                                Link,
                                LinkImage,
                                List,
                                ListProperties,
                                MediaEmbed,
                                PageBreak,
                                Paragraph,
                                PasteFromOffice,
                                RemoveFormat,
                                SelectAll,
                                ShowBlocks,
                                SourceEditing,
                                SpecialCharacters,
                                SpecialCharactersArrows,
                                SpecialCharactersCurrency,
                                SpecialCharactersEssentials,
                                SpecialCharactersLatin,
                                SpecialCharactersMathematical,
                                SpecialCharactersText,
                                Strikethrough,
                                Style,
                                Subscript,
                                Superscript,
                                Table,
                                TableCaption,
                                TableCellProperties,
                                TableColumnResize,
                                TableProperties,
                                TableToolbar,
                                TextTransformation,
                                TodoList,
                                Underline,
                                Undo,
                                InsertMediaPlugin
                            ],
                            toolbar: {
                                items: [
                                    'insertMedia',
                                    '|',
                                    'undo',
                                    'redo',
                                    '|',
                                    'sourceEditing',
                                    'showBlocks',
                                    '|',
                                    'heading',
                                    'style',
                                    '|',
                                    'fontSize',
                                    'fontFamily',
                                    'fontColor',
                                    'fontBackgroundColor',
                                    '|',
                                    'bold',
                                    'italic',
                                    'underline',                                    
                                    '|',
                                    'link',
                                    'insertTable',
                                    'highlight',
                                    'blockQuote',
                                    'codeBlock',
                                    '|',
                                    'alignment',
                                    '|',
                                    'bulletedList',
                                    'numberedList',
                                    'todoList',
                                    'outdent',
                                    'indent'
                                ],
                                shouldNotGroupWhenFull: false
                            },
                            fontFamily: {
                                supportAllValues: true
                            },
                            fontSize: {
                                options: [10, 12, 14, 'default', 18, 20, 22],
                                supportAllValues: true
                            },
                            heading: {
                                options: [
                                    {
                                        model: 'paragraph',
                                        title: 'Paragraph',
                                        class: 'ck-heading_paragraph'
                                    },
                                    {
                                        model: 'heading1',
                                        view: 'h1',
                                        title: 'Heading 1',
                                        class: 'ck-heading_heading1'
                                    },
                                    {
                                        model: 'heading2',
                                        view: 'h2',
                                        title: 'Heading 2',
                                        class: 'ck-heading_heading2'
                                    },
                                    {
                                        model: 'heading3',
                                        view: 'h3',
                                        title: 'Heading 3',
                                        class: 'ck-heading_heading3'
                                    },
                                    {
                                        model: 'heading4',
                                        view: 'h4',
                                        title: 'Heading 4',
                                        class: 'ck-heading_heading4'
                                    },
                                    {
                                        model: 'heading5',
                                        view: 'h5',
                                        title: 'Heading 5',
                                        class: 'ck-heading_heading5'
                                    },
                                    {
                                        model: 'heading6',
                                        view: 'h6',
                                        title: 'Heading 6',
                                        class: 'ck-heading_heading6'
                                    }
                                ]
                            },
                            htmlSupport: {
                                allow: [
                                    {
                                        name: /^.*$/,
                                        styles: true,
                                        attributes: true,
                                        classes: true
                                    }
                                ],
                                disallow: [
                                    {
                                        styles: {
                                            'background-color': true,
                                            'color': true
                                        }
                                    }
                                ]
                            },
                            image: {
                                toolbar: [
                                    'toggleImageCaption',
                                    'imageTextAlternative',
                                    '|',
                                    'imageStyle:inline',
                                    'imageStyle:wrapText',
                                    'imageStyle:breakText',
                                    '|',
                                    'resizeImage'
                                ]
                            },
                            link: {
                                addTargetToExternalLinks: true,
                                defaultProtocol: 'https://',
                                decorators: {
                                    toggleDownloadable: {
                                        mode: 'manual',
                                        label: 'Downloadable',
                                        attributes: {
                                            download: 'file'
                                        }
                                    }
                                }
                            },
                            list: {
                                properties: {
                                    styles: true,
                                    startIndex: true,
                                    reversed: true
                                }
                            },
                            menuBar: {
                                isVisible: false
                            },
                            placeholder: '{{ $placeholder }}',
                            style: {
                                definitions: [
                                    {
                                        name: 'Article category',
                                        element: 'h3',
                                        classes: ['category']
                                    },
                                    {
                                        name: 'Title',
                                        element: 'h2',
                                        classes: ['document-title']
                                    },
                                    {
                                        name: 'Subtitle',
                                        element: 'h3',
                                        classes: ['document-subtitle']
                                    },
                                    {
                                        name: 'Info box',
                                        element: 'p',
                                        classes: ['info-box']
                                    },
                                    {
                                        name: 'Side quote',
                                        element: 'blockquote',
                                        classes: ['side-quote']
                                    },
                                    {
                                        name: 'Marker',
                                        element: 'span',
                                        classes: ['marker']
                                    },
                                    {
                                        name: 'Spoiler',
                                        element: 'span',
                                        classes: ['spoiler']
                                    },
                                    {
                                        name: 'Code (dark)',
                                        element: 'pre',
                                        classes: ['fancy-code', 'fancy-code-dark']
                                    },
                                    {
                                        name: 'Code (bright)',
                                        element: 'pre',
                                        classes: ['fancy-code', 'fancy-code-bright']
                                    }
                                ]
                            },
                            table: {
                                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
                            },

                            @isset($uploadUrl)

                                simpleUpload: {
                                    uploadUrl: '{{ $uploadUrl }}',
                                    withCredentials: true,
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    }
                                }

                            @endisset

                        })
                        .then(editor => {
                            window.ckeditorInstances["ckeditor-" + editorId].instance = editor;
                            let instance = window.ckeditorInstances["ckeditor-" + editorId].instance;

                            // Define mediaPickerStatePath for this editor instance
                            const mediaPickerStatePath = 'ckeditor-media-' + editorId;

                            // Find the main ckeditor class and add some helpful class names to it
                            document.getElementsByClassName('ck-editor__main')[0].classList.add('prose', 'max-w-none', 'dark:prose-invert')

                            const sync = () => {
                                const inst = window.ckeditorInstances["ckeditor-" + editorId];
                                if (!inst?.alpineComponent) return;

                                inst.__fromEditor = true;
                                inst.alpineComponent.state = editor.getData();
                                inst.__fromEditor = false;
                            };

                            // Listen for media selection from media picker modal
                            const handleMediaSelected = (event) => {
                                if (event.detail.statePath !== mediaPickerStatePath) {
                                    return;
                                }

                                const selectedMedia = event.detail.selectedMedia || [];
                                
                                if (selectedMedia.length === 0) {
                                    return;
                                }

                                // Insert each selected media into editor
                                editor.model.change(writer => {
                                    const selection = editor.model.document.selection;
                                    const insertPosition = selection.focus;

                                    selectedMedia.forEach((media, index) => {
                                        // Get media URL
                                        const mediaUrl = media.url || (media.path ? '/storage/' + media.path : '');
                                        
                                        if (!mediaUrl) {
                                            return;
                                        }

                                        // Check if it's an image
                                        if (media.mime_type && media.mime_type.startsWith('image/')) {
                                            // Insert image
                                            const imageElement = writer.createElement('imageBlock', {
                                                src: mediaUrl,
                                                alt: media.name || ''
                                            });
                                            
                                            editor.model.insertContent(imageElement, insertPosition);
                                        } else {
                                            // Insert as link for non-image files
                                            const linkElement = writer.createElement('link', {
                                                href: mediaUrl
                                            }, writer.createText(media.name || 'Media'));
                                            
                                            editor.model.insertContent(linkElement, insertPosition);
                                        }

                                        // Add paragraph break after each media (except last)
                                        if (index < selectedMedia.length - 1) {
                                            const paragraph = writer.createElement('paragraph');
                                            editor.model.insertContent(paragraph, insertPosition);
                                        }
                                    });
                                });
                            };

                            // Add event listener for media selection
                            window.addEventListener('media-selected', handleMediaSelected);
                            
                            // Store handler for cleanup
                            window.ckeditorInstances["ckeditor-" + editorId].mediaSelectedHandler = handleMediaSelected;

                            // Listen to changes (only if not disabled)
                            @if(!$isDisabled)

                                // Update Alpine state immediately on every change (no network calls)
                                editor.model.document.on('change:data', sync);

                                // Flush on Ctrl+S BEFORE Filament triggers save
                                instance.onKeyDown = (e) => {
                                    const isSave = (e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S');
                                    if (!isSave) return;
                                    sync();
                                };

                                window.addEventListener('keydown', instance.onKeyDown, true);

                            @else

                                editor.enableReadOnlyMode('{{ $editorId }}');

                            @endif

                        })
                        .catch(err => {
                            console.error(err);
                        });
                }

                window.destroyCKEditor = function(editorId) {
                    const instanceData = window.ckeditorInstances["ckeditor-" + editorId];
                    if (!instanceData) return;

                    const instance = instanceData.instance;
                    if (!instance) return;

                    // Remove keydown listener
                    if (instance.onKeyDown) {
                        window.removeEventListener('keydown', instance.onKeyDown, true);
                        instance.onKeyDown = null;
                    }

                    // Remove media-selected listener
                    if (instanceData.mediaSelectedHandler) {
                        window.removeEventListener('media-selected', instanceData.mediaSelectedHandler);
                        instanceData.mediaSelectedHandler = null;
                    }

                    instance.destroy();
                }

                // Create bound wrapper functions for event listeners (will be set with Alpine component in init)
                window.ckeditorInstances["ckeditor-{{ $editorId }}"].createHandler = null;
                window.ckeditorInstances["ckeditor-{{ $editorId }}"].destroyHandler = () => destroyCKEditor('{{ $editorId }}');
            </script>
            <div
                x-data="{
                    state: $wire.$entangle('{{ $getStatePath() }}'),
                    init() {
                        const key = 'ckeditor-{{ $editorId }}';

                        window.ckeditorInstances = window.ckeditorInstances || {};
                        const instance = window.ckeditorInstances[key] = window.ckeditorInstances['ckeditor-{{ $editorId }}'] || {};

                        const waitFor = (fnName, cb) => {
                            const t = setInterval(() => {
                                if (typeof window[fnName] === 'function') {
                                    clearInterval(t);
                                    cb();
                                }
                            }, 25);
                        };

                        // Remove existing event listeners to prevent duplicates
                        if (instance?.createHandler) {
                            document.removeEventListener('livewire:navigated', instance.createHandler);
                        }
                        if (instance?.destroyHandler) {
                            document.removeEventListener('livewire:navigate', instance.destroyHandler);
                        }

                        // Create handler with Alpine component context
                        instance.createHandler = () => waitFor('createCKEditor', () => window.createCKEditor('{{ $editorId }}', '{{ $statePath }}', this));

                        instance.destroyHandler = () => waitFor('destroyCKEditor', () => window.destroyCKEditor('{{ $editorId }}'));

                        // Add event listeners if not already added
                        document.addEventListener('livewire:navigated', instance.createHandler);
                        document.addEventListener('livewire:navigate', instance.destroyHandler);

                        // Initialize editor immediately if ClassicEditor is available
                        this.$nextTick(() => {
                            if (!instance.instance) {
                                instance.createHandler();
                            }
                        });

                        // Watch for state changes and update editor content
                        this.$watch('state', (value) => {
                            const editor = instance.instance;

                            if (!editor) return;
                            if (instance.__fromEditor) return;

                            if (value !== null && value !== undefined) {
                                const currentContent = editor.getData();
                                // Only update if content actually changed to prevent loops
                                if (currentContent !== value) {
                                    editor.setData(value);
                                }
                            }
                        });
                    }
                }"
                x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('mks-ckeditor-field', package: 'miran/mksine'))]"
                x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('mks-ckeditor-field', package: 'miran/mksine'))]"
            >
                <textarea
                    id="ckeditor-{{ $editorId }}"
                    name="{{ $name }}"
                    x-model="state"
                ></textarea>
            </div>
        </div>
    </x-filament::input.wrapper>
</x-dynamic-component>