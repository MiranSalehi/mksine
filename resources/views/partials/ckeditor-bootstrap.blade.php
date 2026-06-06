<script>
    window.ckeditorInstances = window.ckeditorInstances || {};
    window.CKEditorFieldConfigs = window.CKEditorFieldConfigs || {};

    window.createCKEditor = function(editorId, statePath, alpineComponent) {
        const config = window.CKEditorFieldConfigs[editorId] || {};
        const placeholder = config.placeholder || 'Type or paste your content here...';
        const height = config.height || '300px';
        const isDisabled = config.isDisabled || false;
        const uploadUrl = config.uploadUrl || '';

        if (window.ckeditorInstances['ckeditor-' + editorId]?.instance) return;
        const textarea = document.querySelector('#ckeditor-' + editorId);
        if (!textarea) return;

        window.ckeditorInstances['ckeditor-' + editorId] = window.ckeditorInstances['ckeditor-' + editorId] || { instance: null };
        window.ckeditorInstances['ckeditor-' + editorId].statePath = statePath;
        window.ckeditorInstances['ckeditor-' + editorId].alpineComponent = alpineComponent;

        const currentEditorIdForMedia = editorId;
        const PluginBase = Object.getPrototypeOf(Essentials.prototype).constructor;
        class InsertMediaPlugin extends PluginBase {
            static get pluginName() { return 'InsertMedia'; }
            init() {
                const editor = this.editor;
                const mediaPickerStatePath = 'ckeditor-media-' + currentEditorIdForMedia;
                const ButtonViewClass = editor.ui.componentFactory.create('bold').constructor;
                editor.ui.componentFactory.add('insertMedia', locale => {
                    const view = new ButtonViewClass(locale);
                    view.set({ label: 'Insert Media', icon: ` <svg xmlns="http://www.w3.org/2000/svg" fill="#000000" width="20px" height="20px" viewBox="0 0 24 24"><path d="M19,13a1,1,0,0,0-1,1v.38L16.52,12.9a2.79,2.79,0,0,0-3.93,0l-.7.7L9.41,11.12a2.85,2.85,0,0,0-3.93,0L4,12.6V7A1,1,0,0,1,5,6h7a1,1,0,0,0,0-2H5A3,3,0,0,0,2,7V19a3,3,0,0,0,3,3H17a3,3,0,0,0,3-3V14A1,1,0,0,0,19,13ZM5,20a1,1,0,0,1-1-1V15.43l2.9-2.9a.79.79,0,0,1,1.09,0l3.17,3.17,0,0L15.46,20Zm13-1a.89.89,0,0,1-.18.53L13.31,15l.7-.7a.77.77,0,0,1,1.1,0L18,17.21ZM22.71,4.29l-3-3a1,1,0,0,0-.33-.21,1,1,0,0,0-.76,0,1,1,0,0,0-.33.21l-3,3a1,1,0,0,0,1.42,1.42L18,4.41V10a1,1,0,0,0,2,0V4.41l1.29,1.3a1,1,0,0,0,1.42,0A1,1,0,0,0,22.71,4.29Z"/></svg>`, tooltip: true });
                    view.on('execute', () => window.dispatchEvent(new CustomEvent('open-media-picker', { detail: { statePath: mediaPickerStatePath, multiple: true, acceptedFileTypes: ['image/*'], currentSelection: [] } })));
                    return view;
                });
            }
        }

        const editorConfig = {
            language: {
                ui: (config.uiLanguage && String(config.uiLanguage).trim()) || 'en',
                content: (config.contentLanguage && String(config.contentLanguage).trim()) || ((config.uiLanguage && String(config.uiLanguage).trim()) || 'en'),
            },
            plugins: [AccessibilityHelp, Alignment, Autoformat, AutoImage, AutoLink, Autosave, BlockQuote, Bold, Code, CodeBlock, Essentials, FindAndReplace, FontBackgroundColor, FontColor, FontFamily, FontSize, GeneralHtmlSupport, Heading, Highlight, HorizontalLine, HtmlComment, HtmlEmbed, ImageBlock, ImageCaption, ImageInline, ImageResize, ImageStyle, ImageTextAlternative, ImageToolbar, Indent, IndentBlock, Italic, Link, LinkImage, List, ListProperties, MediaEmbed, PageBreak, Paragraph, PasteFromOffice, RemoveFormat, SelectAll, ShowBlocks, SourceEditing, SpecialCharacters, SpecialCharactersArrows, SpecialCharactersCurrency, SpecialCharactersEssentials, SpecialCharactersLatin, SpecialCharactersMathematical, SpecialCharactersText, Strikethrough, Style, Subscript, Superscript, Table, TableCaption, TableCellProperties, TableColumnResize, TableProperties, TableToolbar, TextTransformation, TodoList, Underline, Undo, InsertMediaPlugin],
            toolbar: { items: ['insertMedia','|','undo','redo','|','sourceEditing','showBlocks','|','heading','style','|','fontSize','fontFamily','fontColor','fontBackgroundColor','|','bold','italic','underline','|','link','insertTable','highlight','blockQuote','codeBlock','|','alignment','|','bulletedList','numberedList','todoList','outdent','indent'], shouldNotGroupWhenFull: false },
            fontFamily: { supportAllValues: true },
            fontSize: { options: [10, 12, 14, 'default', 18, 20, 22], supportAllValues: true },
            heading: { options: [{ model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },{ model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },{ model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },{ model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },{ model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },{ model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },{ model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }] },
            htmlSupport: { allow: [{ name: /^.*$/, styles: true, attributes: true, classes: true }], disallow: [{ styles: { 'background-color': true, 'color': true } }] },
            image: { toolbar: ['toggleImageCaption', 'imageTextAlternative', '|', 'imageStyle:inline', 'imageStyle:wrapText', 'imageStyle:breakText', '|', 'resizeImage'] },
            link: { addTargetToExternalLinks: true, defaultProtocol: 'https://', decorators: { toggleDownloadable: { mode: 'manual', label: 'Downloadable', attributes: { download: 'file' } } } },
            list: { properties: { styles: true, startIndex: true, reversed: true } },
            menuBar: { isVisible: false },
            placeholder: placeholder,
            style: { definitions: [{ name: 'Article category', element: 'h3', classes: ['category'] },{ name: 'Title', element: 'h2', classes: ['document-title'] },{ name: 'Subtitle', element: 'h3', classes: ['document-subtitle'] },{ name: 'Info box', element: 'p', classes: ['info-box'] },{ name: 'Side quote', element: 'blockquote', classes: ['side-quote'] },{ name: 'Marker', element: 'span', classes: ['marker'] },{ name: 'Spoiler', element: 'span', classes: ['spoiler'] },{ name: 'Code (dark)', element: 'pre', classes: ['fancy-code', 'fancy-code-dark'] },{ name: 'Code (bright)', element: 'pre', classes: ['fancy-code', 'fancy-code-bright'] }] },
            table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties'] }
        };
        if (uploadUrl) editorConfig.simpleUpload = { uploadUrl, withCredentials: true, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' } };

        window.ClassicEditor.create(textarea, editorConfig).then(editor => {
            window.ckeditorInstances['ckeditor-' + editorId].instance = editor;
            const inst = window.ckeditorInstances['ckeditor-' + editorId].instance;
            const mediaPickerStatePath = 'ckeditor-media-' + editorId;
            const wrapper = textarea.closest('[data-ckeditor-id]');
            const mainEl = wrapper?.querySelector('.ck-editor__main');
            const editableEl = wrapper?.querySelector('.ck-editor__editable');
            if (mainEl) { mainEl.classList.add('prose', 'prose-sm', 'max-w-none', 'dark:prose-invert'); mainEl.style.minHeight = height; }
            if (editableEl) editableEl.style.minHeight = height;
            const sync = () => { const i = window.ckeditorInstances['ckeditor-' + editorId]; if (!i?.alpineComponent) return; i.__fromEditor = true; i.alpineComponent.state = editor.getData(); i.__fromEditor = false; };
            const handleMediaSelected = (e) => {
                if (e.detail.statePath !== mediaPickerStatePath) return;
                const selectedMedia = e.detail.selectedMedia || [];
                if (!selectedMedia.length) return;
                editor.model.change(writer => {
                    const selection = editor.model.document.selection;
                    let insertPosition = selection.focus;
                    selectedMedia.forEach((media, index) => {
                        const mediaUrl = media.url || (media.path ? '/storage/' + media.path : '');
                        if (!mediaUrl) return;
                        if (media.mime_type?.startsWith('image/')) {
                            const el = writer.createElement('imageBlock', { src: mediaUrl, alt: media.name || '' });
                            editor.model.insertContent(el, insertPosition);
                        } else {
                            const el = writer.createElement('link', { href: mediaUrl }, writer.createText(media.name || 'Media'));
                            editor.model.insertContent(el, insertPosition);
                        }
                        if (index < selectedMedia.length - 1) {
                            editor.model.insertContent(writer.createElement('paragraph'), insertPosition);
                        }
                    });
                });
            };
            window.addEventListener('media-selected', handleMediaSelected);
            window.ckeditorInstances['ckeditor-' + editorId].mediaSelectedHandler = handleMediaSelected;
            if (!isDisabled) { editor.model.document.on('change:data', sync); inst.onKeyDown = e => { if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) sync(); }; window.addEventListener('keydown', inst.onKeyDown, true); } else editor.enableReadOnlyMode(editorId);
        }).catch(err => console.error(err));
    };

    window.destroyCKEditor = function(editorId) {
        const d = window.ckeditorInstances['ckeditor-' + editorId];
        if (!d?.instance) return;
        if (d.instance.onKeyDown) { window.removeEventListener('keydown', d.instance.onKeyDown, true); d.instance.onKeyDown = null; }
        if (d.mediaSelectedHandler) { window.removeEventListener('media-selected', d.mediaSelectedHandler); d.mediaSelectedHandler = null; }
        d.instance.destroy();
    };
</script>
