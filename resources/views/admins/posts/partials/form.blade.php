@php
    $isEdit = $post?->exists;
    $seoScore = $isEdit ? ($seoInsights ?? ['score' => 0, 'warnings' => []]) : ['score' => 0, 'warnings' => []];
    $mediaImages = $mediaImages ?? [];
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tiêu đề *</label>
                    <input type="text" name="title" class="form-control form-control-lg" value="{{ old('title', $post->title ?? '') }}" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $post->slug ?? '') }}" placeholder="Tự tạo nếu để trống">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Danh mục</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Chọn danh mục --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $post->category_id ?? '') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select name="status" class="form-select">
                            @foreach(['draft'=>'Bản nháp','pending'=>'Chờ duyệt','published'=>'Xuất bản','archived'=>'Lưu trữ'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $post->status ?? 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Thời gian xuất bản</label>
                        <input type="datetime-local" name="published_at" class="form-control"
                               value="{{ old('published_at', isset($post->published_at) ? $post->published_at->format('Y-m-d\TH:i') : '') }}">
                    </div>
                </div>
                <div class="mb-3 form-check form-switch">
                    <input type="hidden" name="is_featured" value="0">
                    <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="isFeaturedSwitch"
                           @checked(old('is_featured', $post->is_featured ?? false))>
                    <label class="form-check-label" for="isFeaturedSwitch">Đặt làm bài viết nổi bật</label>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tóm tắt</label>
                    <textarea name="excerpt" class="form-control" rows="3">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tags</label>
                    
                    <!-- Dropdown để chọn tags có sẵn -->
                    <div class="mb-2">
                        <label class="form-label small text-muted">Chọn từ danh sách có sẵn:</label>
                        <select name="tag_ids[]" id="tagSelect" class="form-select" multiple>
                            @php
                                // Lấy tag IDs từ relationship nếu có post, hoặc từ old input
                                $selectedTagIds = old('tag_ids', []);
                                if (empty($selectedTagIds) && isset($post) && $post->exists) {
                                    // Lấy tags từ relationship
                                    $selectedTagIds = $post->tags()->pluck('id')->toArray();
                                }
                                // Nếu vẫn không có, thử lấy từ tag_ids JSON (backward compatibility)
                                if (empty($selectedTagIds) && isset($post) && !empty($post->tag_ids)) {
                                    $selectedTagIds = is_array($post->tag_ids) ? $post->tag_ids : [];
                                }
                            @endphp
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}" @selected(in_array($tag->id, $selectedTagIds))>
                                    {{ $tag->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Input để thêm tags mới -->
                    <div>
                        <label class="form-label small text-muted">Hoặc thêm tags mới (phân cách bằng dấu phẩy):</label>
                        <input type="text" 
                               name="tag_names" 
                               id="tagNamesInput" 
                               class="form-control" 
                               placeholder="Ví dụ: Fashion, Style, Trend"
                               value="{{ old('tag_names', '') }}">
                        <small class="text-muted">Nhập tên tags mới, phân cách bằng dấu phẩy. Tags mới sẽ được tạo tự động.</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nội dung</label>
                    <textarea id="post-content-editor" name="content" class="form-control" rows="15">{{ old('content', $post->content ?? '') }}</textarea>
                    <small class="text-muted" id="autosave-status">Autosave sẽ hiển thị sau khi bạn chỉnh sửa.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="fw-bold d-flex justify-content-between align-items-center">
                    SEO Score
                    <span class="badge rounded-pill bg-primary" id="seo-score-badge">{{ $seoScore['score'] }}</span>
                </h5>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Meta title</label>
                    <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $post->meta_title ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Meta description</label>
                    <textarea name="meta_description" class="form-control" rows="3">{{ old('meta_description', $post->meta_description ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Meta keywords</label>
                    <input type="text" name="meta_keywords" class="form-control" value="{{ old('meta_keywords', $post->meta_keywords ?? '') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Canonical URL</label>
                    <input type="url" name="meta_canonical" class="form-control" value="{{ old('meta_canonical', $post->meta_canonical ?? '') }}">
                </div>
                <button type="button" class="btn btn-outline-primary w-100" id="seo-analyze-btn">Phân tích SEO</button>
                <ul class="list-unstyled mt-3 text-muted small" id="seo-warning-list">
                    @foreach($seoScore['warnings'] as $warning)
                        <li>• {{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Hình ảnh</h5>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Thumbnail URL</label>
                    <div class="input-group">
                        <input type="text" name="thumbnail" id="thumbnail-input" class="form-control" value="{{ old('thumbnail', $post->thumbnail ?? '') }}">
                        <button type="button" class="btn btn-outline-secondary" onclick="openThumbnailPicker()">Chọn ảnh</button>
                    </div>
                    <div id="thumbnail-preview" class="mt-2">
                        @if(!empty($post->thumbnail))
                            <img src="{{ asset($post->thumbnail) }}" class="img-fluid rounded shadow-sm" alt="preview">
                        @endif
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Alt text</label>
                    <input type="text" name="thumbnail_alt_text" class="form-control" value="{{ old('thumbnail_alt_text', $post->thumbnail_alt_text ?? '') }}">
                </div>
                @if(!empty($post->thumbnail))
                    <img src="{{ asset($post->thumbnail) }}" class="img-fluid rounded shadow-sm" alt="preview">
                @endif
            </div>
        </div>

        @if($isEdit)
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <h5 class="fw-bold mb-0 flex-grow-1">Lịch sử bản thảo</h5>
                        <button class="btn btn-sm btn-outline-secondary" type="button" id="refresh-revision-btn">Refresh</button>
                    </div>
                    <div class="timeline" id="revision-list" style="max-height: 260px; overflow-y:auto;">
                        @forelse($post->revisions()->latest()->limit(10)->get() as $revision)
                            <div class="border rounded p-2 mb-2">
                                <div class="small text-muted">{{ $revision->created_at->diffForHumans() }}</div>
                                <div class="fw-semibold">{{ $revision->editor?->name ?? 'Unknown' }}</div>
                                <div class="badge bg-light text-dark">{{ $revision->is_autosave ? 'Autosave' : 'Manual' }}</div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary w-100 mt-2"
                                    data-restore-url="{{ route('admin.posts.revisions.restore', [$post, $revision]) }}"
                                    onclick="restoreRevision(this)"
                                >
                                    Khôi phục
                                </button>
                            </div>
                        @empty
                            <p class="text-muted">Chưa có lịch sử.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        const autosaveUrl = "{{ $isEdit ? route('admin.posts.autosave', $post) : '' }}";
        const seoAnalyzeUrl = "{{ route('admin.seo.analyze') }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let autosaveTimer;

        if (window.tinymce && document.getElementById('post-content-editor')) {
        const mediaImages = window.mediaImages = window.mediaImages || @json($mediaImages);

        if (window.tinymce && document.getElementById('post-content-editor')) {
            tinymce.init({
                selector: '#post-content-editor',
                menubar: true,
                height: 650,
                plugins: 'code lists link image table media autoresize fullscreen codesample wordcount preview',
                toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media nobi_gallery | table codesample | fullscreen preview',
                skin: 'oxide',
                content_css: 'default',
                automatic_uploads: true,
                file_picker_types: 'image media',
                setup: function (editor) {
                    editor.ui.registry.addButton('nobi_gallery', {
                        text: '🖼 Thư viện',
                        tooltip: 'Chèn ảnh từ thư viện assets',
                        onAction: function () {
                            openMediaPicker(function (file) {
                                editor.insertContent(`<img src="${file.url}" alt="${file.name}">`);
                            });
                        },
                    });

                    editor.on('input', scheduleAutosave);
                    editor.on('change', scheduleAutosave);
                }
            });
        }
        }

        document.querySelectorAll('input[name="title"], textarea[name="excerpt"], input[name="meta_title"], textarea[name="meta_description"]').forEach(el => {
            el.addEventListener('input', scheduleAutosave);
        });

        function scheduleAutosave() {
            if (!autosaveUrl) return;
            clearTimeout(autosaveTimer);
            autosaveTimer = setTimeout(runAutosave, 4000);
        }

        function runAutosave() {
            const statusEl = document.getElementById('autosave-status');
            statusEl.textContent = 'Đang lưu bản nháp...';
            fetch(autosaveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    title: document.querySelector('input[name="title"]').value,
                    excerpt: document.querySelector('textarea[name="excerpt"]').value,
                    content: window.tinymce ? tinymce.get('post-content-editor').getContent() : document.getElementById('post-content-editor').value,
                    meta_title: document.querySelector('input[name="meta_title"]').value,
                    meta_description: document.querySelector('textarea[name="meta_description"]').value,
                    meta_keywords: document.querySelector('input[name="meta_keywords"]').value,
                })
            }).then(res => res.json())
                .then(() => {
                    statusEl.textContent = 'Đã autosave lúc ' + new Date().toLocaleTimeString();
                })
                .catch(() => statusEl.textContent = 'Autosave lỗi. Vui lòng kiểm tra kết nối.');
        }

        document.getElementById('seo-analyze-btn')?.addEventListener('click', function () {
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Đang phân tích...';
            fetch(seoAnalyzeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    title: document.querySelector('input[name="title"]').value,
                    content: window.tinymce ? tinymce.get('post-content-editor').getContent() : document.getElementById('post-content-editor').value,
                    excerpt: document.querySelector('textarea[name="excerpt"]').value,
                    meta_title: document.querySelector('input[name="meta_title"]').value,
                    meta_description: document.querySelector('textarea[name="meta_description"]').value,
                    meta_keywords: document.querySelector('input[name="meta_keywords"]').value,
                    thumbnail: document.querySelector('input[name="thumbnail"]').value,
                    thumbnail_alt_text: document.querySelector('input[name="thumbnail_alt_text"]').value,
                })
            }).then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.textContent = 'Phân tích SEO';
                    document.getElementById('seo-score-badge').textContent = data.data.score;
                    const list = document.getElementById('seo-warning-list');
                    list.innerHTML = '';
                    data.data.warnings.forEach(w => {
                        const li = document.createElement('li');
                        li.textContent = '• ' + w;
                        list.appendChild(li);
                    });
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.textContent = 'Phân tích SEO';
                    alert('Không phân tích được. Thử lại sau.');
                });
        });

        document.getElementById('refresh-revision-btn')?.addEventListener('click', function () {
            fetch("{{ $isEdit ? route('admin.posts.revisions', $post) : '' }}")
                .then(res => res.json())
                .then(res => {
                    const list = document.getElementById('revision-list');
                    list.innerHTML = '';
                    res.data.forEach(revision => {
                        const div = document.createElement('div');
                        div.className = 'border rounded p-2 mb-2';
                        div.innerHTML = `
                            <div class="small text-muted">${new Date(revision.created_at).toLocaleString()}</div>
                            <div class="fw-semibold">${revision.editor?.name ?? 'Unknown'}</div>
                            <div class="badge bg-light text-dark">${revision.is_autosave ? 'Autosave' : 'Manual'}</div>
                        `;
                        list.appendChild(div);
                    });
                });
        });

        function restoreRevision(button) {
            const url = button.dataset.restoreUrl;
            if (!url) return;
            if (!confirm('Khôi phục bản thảo này?')) return;

            const formData = new FormData();
            formData.append('_token', csrfToken);

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            }).then(() => window.location.reload())
              .catch(() => alert('Khôi phục thất bại, vui lòng thử lại.'));
        }

        function openMediaPicker(onSelect) {
            if (!mediaImages.length) {
                alert('Chưa có ảnh trong thư viện clients/assets/img.');
                return;
            }

            const overlay = document.createElement('div');
            overlay.className = 'media-picker-overlay';
            overlay.style.position = 'fixed';
            overlay.style.inset = '0';
            overlay.style.background = 'rgba(15,23,42,0.55)';
            overlay.style.zIndex = '9999';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';

            const modal = document.createElement('div');
            modal.style.background = '#fff';
            modal.style.borderRadius = '16px';
            modal.style.padding = '20px';
            modal.style.width = '580px';
            modal.style.maxHeight = '80vh';
            modal.style.overflowY = 'auto';
            modal.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Chọn ảnh từ thư viện</h5>
                    <button type="button" class="btn btn-link text-danger" data-close>&times;</button>
                </div>
                <div class="row g-3 media-picker-grid">
                    ${mediaImages.map(file => `
                        <div class="col-4">
                            <button type="button" class="w-100 border rounded p-0 bg-white" data-url="${file.url}" data-name="${file.name}">
                                <img src="${file.url}" alt="${file.name}" class="img-fluid" style="height:120px;object-fit:cover;border-top-left-radius:8px;border-top-right-radius:8px;">
                                <div class="p-2 small text-truncate">${file.name}</div>
                            </button>
                        </div>
                    `).join('')}
                </div>
            `;

            const closeModal = () => {
                overlay.removeEventListener('click', handleClick);
                document.body.removeChild(overlay);
            };

            const handleClick = (event) => {
                if (event.target.dataset.close !== undefined || event.target === overlay) {
                    closeModal();
                    return;
                }

                const button = event.target.closest('button[data-url]');
                if (button) {
                    onSelect?.({
                        url: button.dataset.url,
                        name: button.dataset.name,
                    });
                    closeModal();
                }
            };

            overlay.addEventListener('click', handleClick);
            overlay.appendChild(modal);
            document.body.appendChild(overlay);
        }

        function openThumbnailPicker() {
            openMediaPicker(function (file) {
                const input = document.getElementById('thumbnail-input');
                const preview = document.getElementById('thumbnail-preview');
                if (input) {
                    input.value = file.url;
                }
                if (preview) {
                    preview.innerHTML = `<img src="${file.url}" class="img-fluid rounded shadow-sm" alt="${file.name}">`;
                }
            });
        }
    </script>
@endpush

