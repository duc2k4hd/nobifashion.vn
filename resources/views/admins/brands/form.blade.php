@extends('admins.layouts.master')

@php
    $isEdit = $brand->exists;
    $pageTitle = $isEdit ? 'Chỉnh sửa hãng' : 'Tạo hãng mới';
@endphp

@section('title', $pageTitle)
@section('page-title', $pageTitle)

@push('styles')
    <style>
        .card {
            background: #fff;
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 1px 6px rgba(15, 23, 42, 0.06);
            margin-bottom: 16px;
        }

        .card > h3 {
            margin: 0 0 8px;
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 12px 16px;
        }

        .form-control,
        textarea,
        select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #cbd5f5;
            border-radius: 6px;
            font-size: 13px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 4px;
            color: #111827;
        }

        .image-preview img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
    </style>
@endpush

@section('content')
    <form action="{{ $isEdit ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
            <button type="submit" class="btn btn-primary">Lưu hãng</button>
        </div>

        <div class="card">
            <h3>Thông tin cơ bản</h3>
            <div class="grid-3">
                <div>
                    <label>Tên hãng</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $brand->name) }}" required>
                </div>
                <div>
                    <label>Slug</label>
                    <input type="text" name="slug" class="form-control" value="{{ old('slug', $brand->slug) }}" placeholder="tự sinh nếu bỏ trống">
                </div>
                <div>
                    <label>Website</label>
                    <input type="url" name="website" class="form-control" value="{{ old('website', $brand->website) }}" placeholder="https://example.com">
                </div>
                <div>
                    <label>Thứ tự</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $brand->sort_order ?? 0) }}" min="0">
                </div>
                <div>
                    <label>Trạng thái</label>
                    <select name="is_active" class="form-control">
                        <option value="1" {{ old('is_active', $brand->is_active ?? true) ? 'selected' : '' }}>Hiển thị</option>
                        <option value="0" {{ old('is_active', $brand->is_active ?? true) ? '' : 'selected' }}>Tạm ẩn</option>
                    </select>
                </div>
                <div>
                    <label>Logo</label>
                    <input type="file" name="logo" class="form-control">
                    @if($brand->logo)
                        <div class="image-preview" style="margin-top:8px;">
                            <img src="{{ asset(trim((string) config('media.directories.brands', 'clients/assets/img/brands'), '/') . '/' . $brand->logo) }}" alt="{{ $brand->name }}">
                        </div>
                    @endif
                </div>
            </div>
            <div style="margin-top:12px;">
                <label>Mô tả</label>
                <textarea name="description" rows="4" class="form-control">{{ old('description', $brand->description) }}</textarea>
            </div>
        </div>

        <div class="card">
            <h3>SEO Meta</h3>
            <div class="grid-3">
                <div>
                    <label>Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $brand->meta_title) }}">
                </div>
                <div>
                    <label>Meta Canonical</label>
                    <input type="text" name="meta_canonical" class="form-control" value="{{ old('meta_canonical', $brand->meta_canonical) }}">
                </div>
                <div>
                    <label>Meta Keywords</label>
                    <input type="text" name="meta_keywords" class="form-control" value="{{ old('meta_keywords', $brand->meta_keywords) }}">
                </div>
            </div>
            <div style="margin-top:12px;">
                <label>Meta Description</label>
                <textarea name="meta_description" rows="3" class="form-control">{{ old('meta_description', $brand->meta_description) }}</textarea>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:16px;">
            <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
            <button type="submit" class="btn btn-primary">Lưu hãng</button>
        </div>
    </form>
@endsection
