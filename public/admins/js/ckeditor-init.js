/**
 * CKEditor 5 Initialization for NOBI FASHION
 * Thay thế TinyMCE bằng CKEditor 5
 */

(function() {
    'use strict';

    // License key từ CKEditor 5 builder (đã cấu hình cho localhost/127.0.0.1)
    const LICENSE_KEY = 'eyJhbGciOiJFUzI1NiJ9.eyJleHAiOjE3Nzk0OTQzOTksImp0aSI6Ijc3Zjk5MzViLTRlYTgtNDM1MS05NmNmLWViNGE4OTRmMTgzNyIsInVzYWdlRW5kcG9pbnQiOiJodHRwczovL3Byb3h5LWV2ZW50LmNrZWRpdG9yLmNvbSIsImRpc3RyaWJ1dGlvbkNoYW5uZWwiOlsiY2xvdWQiLCJkcnVwYWwiXSwiZmVhdHVyZXMiOlsiRFJVUCIsIkUyUCIsIkUyVyJdLCJ2YyI6ImIzMDI0MDZmIn0.JWi5b64o2vzVfSMBEvCrXVvRquFklSgqxCxcjHYatP9SGI6AaonCt8tQHbh7Y399iwg6Tvbb0q883NyWjXHJ3Q';

    // Kiểm tra CKEditor 5 đã load chưa
    if (typeof window.CKEDITOR === 'undefined') {
        console.error('CKEditor 5 chưa được load. Vui lòng đảm bảo script CKEditor 5 đã được include.');
        return;
    }

    const {
        ClassicEditor,
        Essentials,
        Paragraph,
        Heading,
        Bold,
        Italic,
        Underline,
        Strikethrough,
        Link,
        AutoLink,
        List,
        Alignment,
        BlockQuote,
        CodeBlock,
        HorizontalLine,
        ImageBlock,
        ImageToolbar,
        ImageUpload,
        ImageInsertViaUrl,
        AutoImage,
        ImageTextAlternative,
        ImageCaption,
        ImageStyle,
        ImageUtils,
        ImageEditing,
        Table,
        TableToolbar,
        PlainTableOutput,
        TableCaption,
        SourceEditing,
        Fullscreen,
        GeneralHtmlSupport,
        Autoformat,
        TextTransformation,
        FontSize,
        FontFamily,
        FontColor,
        FontBackgroundColor,
        Highlight,
        Code,
        Subscript,
        Superscript,
        TodoList,
        Indent,
        IndentBlock,
        Style,
        ShowBlocks,
        BalloonToolbar,
        BlockToolbar,
        Plugin,
        ButtonView
    } = window.CKEDITOR;

    // Custom Plugin: Media Library Button
    class MediaLibraryPlugin extends Plugin {
        static get pluginName() {
            return 'MediaLibrary';
        }

        init() {
            const editor = this.editor;
            const context = editor.config.get('mediaLibraryContext') || 'product';

            editor.ui.componentFactory.add('mediaLibrary', locale => {
                const view = new ButtonView(locale);

                view.set({
                    label: 'Thư viện ảnh',
                    icon: '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" viewBox="0 0 503.746 503.746" xml:space="preserve"><g transform="translate(1 1)"><path style="fill:#FFD0A1;" d="M498.479,105.519v358.4c0,18.773-15.36,34.133-34.133,34.133h-358.4c-18.773,0-34.133-15.36-34.133-34.133v-358.4c0-18.773,15.36-34.133,34.133-34.133h325.973h32.427C483.119,71.386,498.479,86.746,498.479,105.519z"/><rect x="105.946" y="105.519" style="fill:#ECF4F7;" width="358.4" height="358.4"/><path style="fill:#50DD8E;" d="M379.012,368.346c14.507-4.267,31.573-6.827,51.2-6.827c0,0,17.067,0,34.133,8.533v93.867h-358.4c0,0,42.667-68.267,102.4-68.267s93.867,34.133,93.867,34.133S319.279,385.412,379.012,368.346z M379.012,190.853c25.6,0,34.133,68.267,34.133,102.4c0,18.773-15.36,34.133-34.133,34.133c-18.773,0-34.133-15.36-34.133-34.133C344.879,259.119,353.412,190.853,379.012,190.853z"/><path style="fill:#FFE079;" d="M232.239,184.026c5.973,5.973,10.24,14.507,10.24,23.893s-3.413,17.92-10.24,23.893c-5.973,5.973-14.507,10.24-23.893,10.24s-17.92-3.413-23.893-10.24c-5.973-5.973-10.24-14.507-10.24-23.893s3.413-17.92,10.24-23.893c5.973-5.973,14.507-10.24,23.893-10.24S226.266,178.053,232.239,184.026"/><path style="fill:#FFD0A1;" d="M71.812,105.519v358.4c-18.773,0-22.187-17.92-22.187-17.92L3.546,85.039c-2.56-18.773,11.093-35.84,29.867-38.4l355.84-43.52c18.773-2.56,35.84,11.093,38.4,29.867l5.12,38.4H105.946C87.172,71.386,71.812,86.746,71.812,105.519"/></g><path style="fill:#51565F;" d="M465.346,503.319h-358.4c-21.333,0-38.4-17.067-38.4-38.4v-358.4c0-21.333,17.067-38.4,38.4-38.4h358.4c21.333,0,38.4,17.067,38.4,38.4v358.4C503.746,486.253,486.679,503.319,465.346,503.319z"/></svg>',
                    tooltip: true,
                    withText: false
                });

                view.on('execute', () => {
                    if (window.mediaLibrary) {
                        window.mediaLibrary.open({
                            context: context,
                            onInsert: (image) => {
                                const alt = image.name.replace(/\.[^/.]+$/, '');
                                const imageUrl = image.url;
                                
                                // Chèn ảnh vào editor
                                editor.model.change(writer => {
                                    const imageElement = writer.createElement('imageBlock', {
                                        src: imageUrl,
                                        alt: alt
                                    });

                                    // Chèn vào vị trí hiện tại
                                    const insertAt = editor.model.document.selection.getFirstPosition();
                                    editor.model.insertContent(imageElement, insertAt);
                                    
                                    // Di chuyển selection sau ảnh
                                    writer.setSelection(imageElement, 'after');
                                });
                            },
                            insertMode: 'single'
                        });
                    } else {
                        alert('Media Library chưa được khởi tạo');
                    }
                });

                return view;
            });
        }
    }

    // Cấu hình editor cho Posts
    const postsEditorConfig = {
        licenseKey: LICENSE_KEY,
        language: 'vi',
        toolbar: {
            shouldNotGroupWhenFull: true,
            items: [
                'undo', 'redo', '|',
                'heading', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'fontSize', 'fontColor', 'fontBackgroundColor', '|',
                'alignment', '|',
                'bulletedList', 'numberedList', 'todoList', '|',
                'link', 'mediaLibrary', 'insertTable', '|',
                'blockQuote', 'codeBlock', 'horizontalLine', '|',
                'sourceEditing', 'fullscreen'
            ]
        },
        plugins: [
            Essentials,
            Paragraph,
            Heading,
            Bold,
            Italic,
            Underline,
            Strikethrough,
            Link,
            AutoLink,
            List,
            Alignment,
            BlockQuote,
            CodeBlock,
            HorizontalLine,
            ImageBlock,
            ImageToolbar,
            ImageUpload,
            ImageInsertViaUrl,
            AutoImage,
            ImageTextAlternative,
            ImageCaption,
            ImageStyle,
            ImageUtils,
            ImageEditing,
            Table,
            TableToolbar,
            PlainTableOutput,
            TableCaption,
            SourceEditing,
            Fullscreen,
            GeneralHtmlSupport,
            Autoformat,
            TextTransformation,
            FontSize,
            FontFamily,
            FontColor,
            FontBackgroundColor,
            Highlight,
            Code,
            Subscript,
            Superscript,
            TodoList,
            Indent,
            IndentBlock,
            Style,
            ShowBlocks,
            BalloonToolbar,
            BlockToolbar,
            MediaLibraryPlugin
        ],
        heading: {
            options: [
                { model: 'paragraph', title: 'Đoạn văn', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Tiêu đề 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Tiêu đề 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Tiêu đề 3', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Tiêu đề 4', class: 'ck-heading_heading4' }
            ]
        },
        fontSize: {
            options: [10, 12, 14, 'default', 18, 20, 22],
            supportAllValues: true
        },
        fontFamily: {
            supportAllValues: true
        },
        htmlSupport: {
            allow: [
                {
                    name: /^.*$/,
                    styles: true,
                    attributes: true,
                    classes: true
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
                'imageStyle:breakText'
            ]
        },
        link: {
            addTargetToExternalLinks: true,
            defaultProtocol: 'https://',
            decorators: {
                openInNewTab: {
                    mode: 'manual',
                    label: 'Mở trong tab mới',
                    attributes: {
                        target: '_blank',
                        rel: 'noopener noreferrer'
                    }
                }
            }
        },
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
        },
        mediaLibraryContext: 'post' // Context cho Media Library
    };

    // Cấu hình editor cho Products (đơn giản hơn)
    const productsEditorConfig = {
        licenseKey: LICENSE_KEY,
        language: 'vi',
        toolbar: {
            items: [
                'undo', 'redo', '|',
                'heading', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'alignment', '|',
                'bulletedList', 'numberedList', '|',
                'link', 'mediaLibrary', 'insertTable', '|',
                'sourceEditing'
            ],
            shouldNotGroupWhenFull: true
        },
        plugins: [
            Essentials,
            Paragraph,
            Heading,
            Bold,
            Italic,
            Underline,
            Strikethrough,
            Link,
            AutoLink,
            List,
            Alignment,
            ImageBlock,
            ImageToolbar,
            ImageUpload,
            ImageInsertViaUrl,
            AutoImage,
            ImageTextAlternative,
            ImageCaption,
            ImageStyle,
            ImageUtils,
            ImageEditing,
            Table,
            TableToolbar,
            PlainTableOutput,
            TableCaption,
            SourceEditing,
            GeneralHtmlSupport,
            Autoformat,
            TextTransformation,
            MediaLibraryPlugin
        ],
        heading: {
            options: [
                { model: 'paragraph', title: 'Đoạn văn', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Tiêu đề 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Tiêu đề 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Tiêu đề 3', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Tiêu đề 4', class: 'ck-heading_heading4' }
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
            ]
        },
        image: {
            toolbar: [
                'toggleImageCaption',
                'imageTextAlternative',
                '|',
                'imageStyle:inline',
                'imageStyle:wrapText',
                'imageStyle:breakText'
            ]
        },
        link: {
            addTargetToExternalLinks: true,
            defaultProtocol: 'https://',
            decorators: {
                openInNewTab: {
                    mode: 'manual',
                    label: 'Mở trong tab mới',
                    attributes: {
                        target: '_blank',
                        rel: 'noopener noreferrer'
                    }
                }
            }
        },
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
        },
        mediaLibraryContext: 'product' // Context cho Media Library
    };

    // Khởi tạo editor cho Posts
    function initPostsEditor() {
        const textarea = document.getElementById('post-content-editor');
        if (!textarea) return;

        const initialData = textarea.value || '';

        ClassicEditor
            .create(textarea, {
                ...postsEditorConfig,
                initialData: initialData
            })
            .then(editor => {
                window.ckeditorInstances = window.ckeditorInstances || {};
                window.ckeditorInstances['post-content-editor'] = editor;

                // Sync với textarea khi submit form
                const form = textarea.closest('form');
                if (form) {
                    form.addEventListener('submit', () => {
                        // Đảm bảo lưu HTML, không phải Markdown
                        const htmlContent = editor.getData();
                        textarea.value = htmlContent;
                        console.log('Saving HTML content:', htmlContent.substring(0, 100));
                    });
                }

                // Event cho autosave
                editor.model.document.on('change:data', () => {
                    if (window.scheduleAutosave) {
                        window.scheduleAutosave();
                    }
                });

                console.log('CKEditor 5 initialized for posts');
            })
            .catch(error => {
                console.error('Error initializing CKEditor 5 for posts:', error);
            });
    }

    // Khởi tạo editor cho Products
    function initProductsEditors() {
        document.querySelectorAll('.tinymce-editor').forEach(textarea => {
            // Tránh khởi tạo lại
            if (textarea.dataset.ckeditorInitialized === 'true') {
                return;
            }

            const initialData = textarea.value || '';
            const editorId = textarea.id || 'ckeditor-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            if (!textarea.id) {
                textarea.id = editorId;
            }

            // Xác định context dựa trên form
            const isProductForm = textarea.closest('form#product-form');
            const config = isProductForm ? productsEditorConfig : postsEditorConfig;

            ClassicEditor
                .create(textarea, {
                    ...config,
                    initialData: initialData
                })
                .then(editor => {
                    textarea.dataset.ckeditorInitialized = 'true';
                    window.ckeditorInstances = window.ckeditorInstances || {};
                    window.ckeditorInstances[editorId] = editor;

                    // Sync với textarea khi submit form
                    const form = textarea.closest('form');
                    if (form) {
                        form.addEventListener('submit', () => {
                            // Đảm bảo lưu HTML, không phải Markdown
                            const htmlContent = editor.getData();
                            textarea.value = htmlContent;
                            console.log('Saving HTML content for:', editorId, htmlContent.substring(0, 100));
                        });
                    }

                    console.log('CKEditor 5 initialized for:', editorId);
                })
                .catch(error => {
                    console.error('Error initializing CKEditor 5:', error);
                });
        });
    }

    // API tương đương TinyMCE
    window.CKEditor5API = {
        get: function(editorId) {
            return window.ckeditorInstances && window.ckeditorInstances[editorId] || null;
        },
        getContent: function(editorId) {
            const editor = this.get(editorId);
            if (!editor) return '';
            // Đảm bảo trả về HTML, không phải Markdown
            const htmlContent = editor.getData();
            console.log('Getting HTML content for:', editorId, htmlContent.substring(0, 100));
            return htmlContent;
        },
        setContent: function(editorId, content) {
            const editor = this.get(editorId);
            if (editor) {
                editor.setData(content);
            }
        }
    };

    // Auto-init khi DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initPostsEditor();
            initProductsEditors();
        });
    } else {
        initPostsEditor();
        initProductsEditors();
    }

    // Observer để tự động khởi tạo editor cho các textarea mới được thêm vào
    if (typeof MutationObserver !== 'undefined') {
        const editorObserver = new MutationObserver((mutations) => {
            let shouldInit = false;
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        if (node.tagName === 'TEXTAREA' && 
                            (node.id === 'post-content-editor' || node.classList.contains('tinymce-editor'))) {
                            shouldInit = true;
                        } else if (node.querySelectorAll) {
                            const textareas = node.querySelectorAll('#post-content-editor, .tinymce-editor');
                            if (textareas.length > 0) {
                                shouldInit = true;
                            }
                        }
                    }
                });
            });
            if (shouldInit) {
                setTimeout(() => {
                    initPostsEditor();
                    initProductsEditors();
                }, 100);
            }
        });

        if (document.body) {
            editorObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

})();
