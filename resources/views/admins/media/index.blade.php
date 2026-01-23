@extends('admins.layouts.master')

@section('title', 'Quản lý Media')
@section('page-title', '📷 Media Manager')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/media-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .media-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .media-card {
            border-radius: 16px;
            padding: 20px;
            color: #fff;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
        }
        .media-card h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        .media-card p {
            margin: 4px 0 0;
            font-size: 28px;
            font-weight: 700;
        }
        .media-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            align-items: center;
        }
        .media-controls select,
        .media-controls input {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
        }
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 18px;
        }
        .media-item {
            border-radius: 14px;
            background: #fff;
            border: 1px solid #edf2f7;
            box-shadow: 0 4px 8px rgba(0,0,0,0.04);
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .media-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.08);
        }
        .media-thumb {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
            background: #f8fafc;
        }
        .media-body {
            padding: 12px 14px 16px;
        }
        .media-body h5 {
            font-size: 15px;
            margin-bottom: 6px;
            font-weight: 600;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }
        .media-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            font-size: 12px;
        }
        .media-tag {
            border-radius: 999px;
            padding: 2px 10px;
            background: #eef2ff;
            color: #4338ca;
            font-weight: 600;
        }
        .media-upload-card {
            border-radius: 18px;
            background: #ffffff;
            padding: 20px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        }
        .media-upload-card h3 {
            font-weight: 700;
            margin-bottom: 18px;
        }
        .media-upload-card .form-control,
        .media-upload-card .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        .media-upload-dropzone {
            border: 2px dashed #a5b4fc;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            color: #6366f1;
            cursor: pointer;
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        .media-upload-dropzone.dragover {
            border-color: #4c1d95;
            background: rgba(99,102,241,0.05);
        }
        .media-empty {
            text-align: center;
            padding: 50px 0;
            color: #94a3b8;
            font-size: 16px;
        }
        .media-pagination {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
        }
        .media-pagination button {
            border: none;
            background: #e0e7ff;
            color: #312e81;
            padding: 10px 18px;
            border-radius: 999px;
            font-weight: 600;
        }
        .media-pagination button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="media-dashboard">
            <div class="media-card">
                <h4>Tổng số ảnh</h4>
                <p>{{ number_format($stats['total_images']) }}</p>
            </div>
            <div class="media-card" style="background: linear-gradient(135deg,#06b6d4,#3b82f6);">
                <h4>Ảnh sản phẩm</h4>
                <p>{{ number_format($stats['product_images']) }}</p>
            </div>
            <div class="media-card" style="background: linear-gradient(135deg,#ec4899,#c026d3);">
                <h4>Ảnh bài viết</h4>
                <p>{{ number_format($stats['post_thumbnails']) }}</p>
            </div>
            <div class="media-card" style="background: linear-gradient(135deg,#10b981,#14b8a6);">
                <h4>Ảnh danh mục</h4>
                <p>{{ number_format($stats['category_images']) }}</p>
            </div>
            <div class="media-card" style="background: linear-gradient(135deg,#f97316,#ef4444);">
                <h4>Ảnh banners</h4>
                <p>{{ number_format($stats['banner_images']) }}</p>
            </div>
            <div class="media-card" style="background: linear-gradient(135deg,#9333ea,#7c3aed);">
                <h4>Avatar hồ sơ</h4>
                <p>{{ number_format($stats['profile_avatars']) }}</p>
            </div>
            <div class="media-card" style="background: linear-gradient(135deg,#0ea5e9,#2563eb);">
                <h4>Dung lượng ước tính</h4>
                <p>{{ $stats['estimated_size'] }}</p>
            </div>
        </div>

        <div class="media-upload-card">
            <h3>Upload nhanh</h3>
            <form id="mediaUploadForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Thư mục đích</label>
                        <select name="folder" class="form-select" required>
                            @foreach($folders->groupBy('scope') as $scope => $options)
                                <optgroup label="{{ $scope }}">
                                    @foreach($options as $folder)
                                        <option value="{{ $folder['key'] }}">
                                            {{ $folder['label'] }} ({{ $folder['path'] }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Gán cho</label>
                        <select name="target_type" id="mediaTargetType" class="form-select" required>
                            @foreach($uploadTargets as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Đối tượng</label>
                        <select name="target_id" id="mediaTargetSelect" placeholder="Chọn đối tượng..." required></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Đặt làm ảnh chính?</label>
                        <select name="is_primary" class="form-select">
                            <option value="0">Không</option>
                            <option value="1">Có</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tiêu đề</label>
                        <input type="text" class="form-control" name="title" placeholder="Tiêu đề ảnh">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Alt text</label>
                        <input type="text" class="form-control" name="alt" placeholder="Mô tả SEO">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Caption / mô tả</label>
                        <input type="text" class="form-control" name="description" placeholder="Chú thích">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Chọn ảnh</label>
                        <div class="media-upload-dropzone" id="mediaDropzone">
                            Kéo thả ảnh vào đây hoặc bấm để chọn
                            <input type="file" name="files[]" id="mediaFileInput" accept="image/*" multiple hidden>
                        </div>
                        <p class="text-muted mt-2" id="mediaSelectedFiles">Chưa chọn file nào.</p>
                    </div>
                    <div class="col-12 text-end mt-2">
                        <button type="submit" class="btn btn-primary px-4">Tải lên &amp; gán</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="media-controls">
            <select id="mediaFilterType" class="form-select" style="max-width: 220px;">
                @foreach($filters as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select id="mediaSort" class="form-select" style="max-width: 200px;">
                <option value="created_at">Mới nhất</option>
                <option value="file_name">Tên file</option>
                <option value="entity_id">ID đối tượng</option>
            </select>
            <input type="search" id="mediaKeyword" class="form-control" placeholder="Tìm theo tên file, alt, title..." style="max-width: 280px;">
            <button class="btn btn-outline-secondary" id="mediaSearchBtn">Tìm kiếm</button>
        </div>

        <div id="mediaGridWrapper">
            <div class="media-grid" id="mediaGrid"></div>
            <div class="media-pagination" id="mediaPagination" hidden>
                <button id="mediaPrevBtn">← Trước</button>
                <button id="mediaNextBtn">Sau →</button>
            </div>
            <div class="media-empty" id="mediaEmptyState" hidden>
                Không tìm thấy media nào cho bộ lọc hiện tại.
            </div>
        </div>
    </div>

    @include('admins.media.modal-picker')
    @include('admins.media.editor')
@endsection

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        const csrfToken = '{{ csrf_token() }}';
        const mediaFallback = @json(asset('clients/assets/og-default.jpg'));
        const mediaFolderLabels = @json($folderLabels ?? []);
        const mediaTypeLabels = @json($typeLabels ?? []);

        const mediaState = {
            items: @json($initialMedia),
            meta: @json($initialPagination),
            filters: {
                type: 'all',
                sort: 'created_at',
                direction: 'desc',
                q: '',
                page: 1,
            }
        };

        function renderMediaGrid() {
            const grid = document.getElementById('mediaGrid');
            const empty = document.getElementById('mediaEmptyState');
            const pagination = document.getElementById('mediaPagination');

            grid.innerHTML = '';
            if (!mediaState.items.length) {
                empty.hidden = false;
                pagination.hidden = true;
                return;
            }

            empty.hidden = true;
            pagination.hidden = mediaState.meta.last_page <= 1;

            mediaState.items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'media-item';
                div.dataset.id = item.id;
                div.dataset.type = item.type;
                const badgeLabel = item.folder_label
                    || mediaFolderLabels[item.folder_key]
                    || item.type_label
                    || mediaTypeLabels[item.type]
                    || item.type;
                div.innerHTML = `
                    <img class="media-thumb" src="${item.preview || item.original || mediaFallback}" alt="${item.alt || ''}">
                    <div class="media-body">
                        <h5 title="${item.file_name || ''}">${item.file_name || 'Không rõ tên'}</h5>
                        <div class="media-tags">
                            <span class="media-tag">${badgeLabel}</span>
                            ${item.entity_label ? `<span class="media-tag" style="background:#ecfccb;color:#365314;">${item.entity_label}</span>` : ''}
                        </div>
                    </div>
                `;
                div.addEventListener('click', () => openMediaEditor(item));
                grid.appendChild(div);
            });

            document.getElementById('mediaPrevBtn').disabled = mediaState.meta.current_page <= 1;
            document.getElementById('mediaNextBtn').disabled = mediaState.meta.current_page >= mediaState.meta.last_page;
        }

        function fetchMedia(page = 1) {
            mediaState.filters.page = page;
            const params = new URLSearchParams(mediaState.filters);
            fetch(`{{ route('admin.media.search') }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    mediaState.items = data.data;
                    mediaState.meta = data.meta;
                    renderMediaGrid();
                })
                .catch(() => {
                    alert('Không thể tải media. Vui lòng thử lại.');
                });
        }

        function openMediaEditor(item) {
            const modal = document.getElementById('mediaEditorModal');
            modal.dataset.id = item.id;
            modal.dataset.type = item.type;
            modal.querySelector('[name="title"]').value = item.title || '';
            modal.querySelector('[name="alt"]').value = item.alt || '';
            modal.querySelector('[name="description"]').value = item.description || '';
            modal.querySelector('[name="is_primary"]').checked = item.metadata?.is_primary || false;
            modal.querySelector('.media-editor-preview').src = item.original || item.preview || '';
            document.getElementById('mediaAssignBtn').onclick = () => openAssignModal(item);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }

        function openAssignModal(item) {
            const modal = document.getElementById('mediaAssignModal');
            modal.querySelector('[name="media_id"]').value = item.id;
            modal.querySelector('[name="source"]').value = item.type;
            const assignModal = new bootstrap.Modal(modal);
            assignModal.show();
        }

        function submitAssignForm() {
            const modal = document.getElementById('mediaAssignModal');
            const form = document.getElementById('mediaAssignForm');
            const formData = new FormData(form);

            fetch(`{{ route('admin.media.assign') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        bootstrap.Modal.getInstance(modal).hide();
                        fetchMedia(mediaState.meta.current_page);
                    }
                })
                .catch(() => alert('Không thể gán ảnh.'));
        }

        function saveMediaEditor() {
            const modal = document.getElementById('mediaEditorModal');
            const id = modal.dataset.id;
            const type = modal.dataset.type;
            const form = modal.querySelector('form');
            const formData = new FormData(form);
            formData.append('source', type);

            fetch(`{{ url('/admin/media/update') }}/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        bootstrap.Modal.getInstance(modal).hide();
                        fetchMedia(mediaState.meta.current_page);
                    }
                })
                .catch(() => alert('Không thể lưu media.'));
        }

        function deleteMedia() {
            if (!confirm('Xoá ảnh này?')) return;
            const modal = document.getElementById('mediaEditorModal');
            const id = modal.dataset.id;
            const type = modal.dataset.type;
            const formData = new FormData();
            formData.append('source', type);

            fetch(`{{ url('/admin/media/delete') }}/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        bootstrap.Modal.getInstance(modal).hide();
                        fetchMedia(mediaState.meta.current_page);
                    }
                })
                .catch(() => alert('Không thể xoá media.'));
        }

        const targetTypeSelect = document.getElementById('mediaTargetType');
        const targetSelect = new TomSelect('#mediaTargetSelect', {
            valueField: 'id',
            labelField: 'label',
            searchField: 'label',
            loadThrottle: 300,
            maxItems: 1,
            persist: false,
            create: false,
            placeholder: 'Chọn đối tượng...',
            load: function (query, callback) {
                const type = targetTypeSelect.value;
                if (!type) {
                    return callback();
                }
                const url = new URL(`{{ route('admin.media.targets') }}`);
                url.searchParams.set('type', type);
                if (query) {
                    url.searchParams.set('q', query);
                }

                fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(json => {
                        callback(json.data || []);
                    })
                    .catch(() => callback());
            },
            render: {
                option: function (item, escape) {
                    return `<div>
                        <div class="option-title">${escape(item.label)}</div>
                        ${item.description ? `<div class="option-desc text-muted">${escape(item.description)}</div>` : ''}
                    </div>`;
                },
                item: function (item, escape) {
                    return `<div>${escape(item.label)}</div>`;
                }
            }
        });

        targetTypeSelect.addEventListener('change', () => {
            targetSelect.clear();
            targetSelect.clearOptions();
        });

        function submitUploadForm(e) {
            e.preventDefault();
            const form = document.getElementById('mediaUploadForm');
            const filesInput = document.getElementById('mediaFileInput');

            if (!filesInput.files.length) {
                alert('Vui lòng chọn ít nhất một ảnh.');
                return;
            }

            const formData = new FormData(form);

            fetch(`{{ route('admin.media.upload') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        form.reset();
                        document.getElementById('mediaSelectedFiles').innerText = 'Chưa chọn file nào.';
                        fetchMedia();
                    }
                })
                .catch(() => alert('Upload thất bại. Hãy thử lại.'));
        }

        function initUploadDropzone() {
            const dropzone = document.getElementById('mediaDropzone');
            const fileInput = document.getElementById('mediaFileInput');
            const label = document.getElementById('mediaSelectedFiles');

            dropzone.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', () => {
                if (fileInput.files.length) {
                    label.innerText = `${fileInput.files.length} file đã chọn.`;
                } else {
                    label.innerText = 'Chưa chọn file nào.';
                }
            });

            ;['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, e => {
                    e.preventDefault();
                    dropzone.classList.add('dragover');
                });
            });

            ;['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, e => {
                    e.preventDefault();
                    dropzone.classList.remove('dragover');
                });
            });

            dropzone.addEventListener('drop', e => {
                fileInput.files = e.dataTransfer.files;
                label.innerText = `${fileInput.files.length} file đã chọn.`;
            });
        }

        document.getElementById('mediaFilterType').addEventListener('change', (e) => {
            mediaState.filters.type = e.target.value;
            fetchMedia(1);
        });
        document.getElementById('mediaSort').addEventListener('change', (e) => {
            mediaState.filters.sort = e.target.value;
            fetchMedia(1);
        });
        document.getElementById('mediaSearchBtn').addEventListener('click', () => {
            mediaState.filters.q = document.getElementById('mediaKeyword').value;
            fetchMedia(1);
        });
        document.getElementById('mediaPrevBtn').addEventListener('click', () => {
            if (mediaState.meta.current_page > 1) {
                fetchMedia(mediaState.meta.current_page - 1);
            }
        });
        document.getElementById('mediaNextBtn').addEventListener('click', () => {
            if (mediaState.meta.current_page < mediaState.meta.last_page) {
                fetchMedia(mediaState.meta.current_page + 1);
            }
        });
        document.getElementById('mediaUploadForm').addEventListener('submit', submitUploadForm);
        document.getElementById('mediaAssignSubmitBtn').addEventListener('click', submitAssignForm);
        initUploadDropzone();
        renderMediaGrid();

        window.mediaManagerActions = {
            saveMediaEditor,
            deleteMedia,
        };
    </script>
@endpush


