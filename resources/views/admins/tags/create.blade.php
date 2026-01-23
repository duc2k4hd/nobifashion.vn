@extends('admins.layouts.master')

@section('title', 'Tạo Tag mới')
@section('page-title', '➕ Tạo Tag mới')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/tags-icon.png') }}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Tạo Tag mới</h2>
            <a href="{{ route('admin.tags.index') }}" class="btn btn-outline-secondary">← Quay lại</a>
        </div>

        <form action="{{ route('admin.tags.store') }}" method="POST">
            @csrf
            @include('admins.tags.partials.form', [
                'tag' => $tag,
                'entityTypes' => $entityTypes,
            ])
            <div style="margin-top:20px;display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">💾 Lưu Tag</button>
                <a href="{{ route('admin.tags.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto generate slug from name
            const nameInput = document.querySelector('input[name="name"]');
            const slugInput = document.querySelector('input[name="slug"]');
            
            if (nameInput && slugInput) {
                nameInput.addEventListener('blur', function() {
                    if (!slugInput.value) {
                        const slug = this.value.toLowerCase()
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '')
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-+|-+$/g, '');
                        slugInput.value = slug;
                    }
                });
            }

            // Entity type select
            const entityTypeSelect = document.querySelector('select[name="entity_type"]');
            if (entityTypeSelect) {
                new TomSelect(entityTypeSelect, {
                    placeholder: 'Chọn loại entity...',
                    allowEmptyOption: false,
                    create: false,
                });
            }

            // Entity autocomplete
            const entityIdInput = document.getElementById('entity_id_input');
            const entityIdHidden = document.getElementById('entity_id');
            const entityTypeSelectEl = document.querySelector('select[name="entity_type"]');
            
            if (entityIdInput && entityIdHidden && entityTypeSelectEl) {
                let tomSelect = null;
                
                function initEntityAutocomplete() {
                    const entityType = entityTypeSelectEl.value;
                    if (!entityType) {
                        if (tomSelect) {
                            tomSelect.destroy();
                            tomSelect = null;
                        }
                        entityIdInput.disabled = true;
                        return;
                    }

                    entityIdInput.disabled = false;
                    
                    if (tomSelect) {
                        tomSelect.destroy();
                    }

                    tomSelect = new TomSelect(entityIdInput, {
                        placeholder: 'Tìm kiếm và chọn...',
                        valueField: 'id',
                        labelField: 'name',
                        searchField: 'name',
                        create: false,
                        load: function(query, callback) {
                            if (!query.length) return callback();
                            
                            fetch(`{{ route('admin.tags.entities') }}?entity_type=${entityType}&keyword=${encodeURIComponent(query)}`)
                                .then(res => res.json())
                                .then(data => callback(data))
                                .catch(() => callback());
                        },
                        onChange: function(value) {
                            entityIdHidden.value = value;
                        },
                    });
                }

                entityTypeSelectEl.addEventListener('change', initEntityAutocomplete);
                initEntityAutocomplete();
            }
        });
    </script>
@endpush

