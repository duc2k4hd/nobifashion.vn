@extends('admins.layouts.master')

@php
    $isEdit = $brand->exists;
    $pageTitle = $isEdit ? 'Chỉnh sửa thương hiệu: ' . $brand->name : 'Thêm thương hiệu mới';
@endphp

@section('title', $pageTitle)
@section('page-title', $isEdit ? 'Chỉnh sửa thương hiệu' : 'Thêm thương hiệu')

@push('styles')
    <style>
        .form-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .form-title-group h2 {
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-title-group p {
            margin: 0;
            color: #64748b;
            font-size: 13.5px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modern-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #edf2f7;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 14px rgba(15, 23, 42, 0.03);
            margin-bottom: 22px;
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }

        .modern-card:hover {
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
        }

        .card-header-styled {
            padding: 16px 22px;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-styled h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header-styled h3 i {
            color: #3b82f6;
            font-size: 16px;
        }

        .card-body-styled {
            padding: 22px;
        }

        .form-label-styled {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-label-styled .req {
            color: #ef4444;
            margin-left: 2px;
        }

        .form-control-styled {
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .form-control-styled:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }

        .form-helper {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 5px;
            line-height: 1.4;
        }

        .btn-modern {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-modern-primary {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .btn-modern-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
            transform: translateY(-1px);
        }

        .btn-modern-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-modern-secondary:hover {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-modern-success {
            background: #10b981;
            color: #ffffff;
        }

        .btn-modern-success:hover {
            background: #059669;
            color: #ffffff;
        }

        .btn-modern-danger {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-modern-danger:hover {
            background: #fecaca;
            color: #b91c1c;
        }

        /* Upload Logo Box */
        .logo-upload-wrapper {
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            padding: 24px 16px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
        }

        .logo-upload-wrapper:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .logo-upload-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .logo-preview-box {
            width: 140px;
            height: 140px;
            border-radius: 14px;
            border: 2px solid #e2e8f0;
            background: #ffffff;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            padding: 6px;
        }

        .logo-preview-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Repeater Cards (Banner slides & FAQs) */
        .repeater-item-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 14px;
            position: relative;
            transition: all 0.2s;
        }

        .repeater-item-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .repeater-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #e2e8f0;
        }

        .repeater-item-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-remove-item {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #fee2e2;
            color: #ef4444;
            border: 1px solid #fecaca;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .btn-remove-item:hover {
            background: #ef4444;
            color: #fff;
        }

        /* Google Preview Box */
        .google-preview-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
            margin-top: 14px;
            font-family: Arial, sans-serif;
        }

        .google-preview-url {
            font-size: 12px;
            color: #202124;
            line-height: 1.3;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .google-preview-title {
            font-size: 17px;
            color: #1a0dab;
            line-height: 1.3;
            font-weight: 400;
            cursor: pointer;
            text-decoration: none;
            display: block;
            margin-bottom: 4px;
        }

        .google-preview-title:hover {
            text-decoration: underline;
        }

        .google-preview-desc {
            font-size: 13px;
            color: #4d5156;
            line-height: 1.5;
            margin: 0;
        }

        .char-counter {
            font-size: 11.5px;
            color: #94a3b8;
            float: right;
        }
    </style>
@endpush

@section('content')
    <form action="{{ $isEdit ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" method="POST" enctype="multipart/form-data" id="brand-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Thanh Header hành động --}}
        <div class="form-header-bar">
            <div class="form-title-group">
                <h2>
                    <i class="fa-solid fa-tags text-primary"></i>
                    {{ $pageTitle }}
                    @if($isEdit)
                        <span class="badge" style="background: #eff6ff; color: #2563eb; font-size: 12px; padding: 4px 10px; border-radius: 8px;">ID #{{ $brand->id }}</span>
                    @endif
                </h2>
                <p>Quản lý hồ sơ thương hiệu, thông số nổi bật, slide trình chiếu và tối ưu hóa SEO.</p>
            </div>
            <div class="header-actions">
                @if($isEdit)
                    <a href="{{ route('client.brand.show', $brand->slug) }}" target="_blank" class="btn-modern btn-modern-secondary" title="Xem gian hàng ngoài website">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem gian hàng
                    </a>
                @endif
                <a href="{{ route('admin.brands.index') }}" class="btn-modern btn-modern-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Danh sách
                </a>
                <button type="submit" class="btn-modern btn-modern-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Lưu thương hiệu
                </button>
            </div>
        </div>

        {{-- Nội dung 2 cột --}}
        <div class="row g-4">
            {{-- Cột trái (8 cols): Nội dung chính --}}
            <div class="col-lg-8">
                {{-- Card 1: Thông tin cơ bản --}}
                <div class="modern-card">
                    <div class="card-header-styled">
                        <h3><i class="fa-solid fa-circle-info"></i> Thông tin nhận diện cơ bản</h3>
                    </div>
                    <div class="card-body-styled">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-styled">Tên thương hiệu <span class="req">*</span></label>
                                <input type="text" name="name" id="brand-name-input" class="form-control-styled" value="{{ old('name', $brand->name) }}" placeholder="Ví dụ: NOBIFASHION, Yody..." required>
                                <div class="form-helper">Tên chính thức hiển thị trên toàn bộ hệ thống.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-styled">Slug (Đường dẫn thân thiện)</label>
                                <input type="text" name="slug" id="brand-slug-input" class="form-control-styled" value="{{ old('slug', $brand->slug) }}" placeholder="Ví dụ: nobifashion, yody (tự sinh nếu để trống)">
                                <div class="form-helper">Đường dẫn: <code>/brand/{slug}</code></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label-styled">Website chính thức</label>
                                <input type="url" name="website" class="form-control-styled" value="{{ old('website', $brand->website) }}" placeholder="https://yody.vn">
                                <div class="form-helper">Trang web của hãng (nếu có).</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label-styled">Mô tả / Câu chuyện thương hiệu</label>
                                <textarea name="description" rows="4" class="form-control-styled" placeholder="Nhập tóm tắt giới thiệu, tôn chỉ hoặc câu chuyện về thương hiệu...">{{ old('description', $brand->description) }}</textarea>
                                <div class="form-helper">Hiển thị ở đầu trang chi tiết gian hàng thương hiệu.</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Thông số thương hiệu & Chiến dịch (Các trường dữ liệu mới) --}}
                <div class="modern-card">
                    <div class="card-header-styled">
                        <h3><i class="fa-solid fa-chart-line"></i> Chỉ số thương hiệu & Chiến dịch</h3>
                    </div>
                    <div class="card-body-styled">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-styled">Slogan chiến dịch (Campaign Tag)</label>
                                <input type="text" name="campaign" class="form-control-styled" value="{{ old('campaign', $brand->campaign) }}" placeholder="Ví dụ: #LOOK GOOD FEEL GOOD, #SUMMER 2026">
                                <div class="form-helper">Hiển thị dạng hashtag nổi bật trên trang thương hiệu.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-styled">Số năm hoạt động / gia nhập</label>
                                <div style="position: relative;">
                                    <input type="number" name="joined_years" class="form-control-styled" value="{{ old('joined_years', $brand->joined_years ?? 5) }}" min="0" max="100">
                                </div>
                                <div class="form-helper">Hiển thị "X năm đồng hành" trên thanh huy hiệu thương hiệu.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-styled">Lượt người theo dõi (Followers)</label>
                                <input type="number" name="followers_count" class="form-control-styled" value="{{ old('followers_count', $brand->followers_count ?? 1500) }}" min="0">
                                <div class="form-helper">Số lượng người theo dõi (VD: 5400).</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-styled">Điểm đánh giá (Rating Score: 0 - 5.0)</label>
                                <input type="number" step="0.01" name="rating_score" class="form-control-styled" value="{{ old('rating_score', $brand->rating_score ?? 4.90) }}" min="0" max="5">
                                <div class="form-helper">Điểm sao uy tín (VD: 4.95 ⭐).</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card: Sản phẩm thuộc thương hiệu --}}
                <div class="modern-card">
                    <div class="card-header-styled">
                        <h3>
                            <i class="fa-solid fa-shirt text-primary"></i> Sản phẩm thuộc thương hiệu
                            <span class="badge" style="background: #eff6ff; color: #2563eb; font-size: 12px; padding: 4px 10px; border-radius: 8px;" id="brand-products-count-badge">
                                {{ number_format($totalBrandProducts ?? 0) }} sản phẩm
                            </span>
                        </h3>
                        <button type="button" class="btn-modern btn-modern-primary btn-sm" id="btn-open-attach-modal">
                            <i class="fa-solid fa-plus"></i> Thêm sản phẩm vào hãng
                        </button>
                    </div>
                    <div class="card-body-styled">
                        <div class="form-helper" style="margin-bottom: 14px;">
                            Quản lý danh sách các sản phẩm được gắn vào thương hiệu này. Bạn có thể mở popup để tìm kiếm cực nhanh và chọn sản phẩm gán vào hãng.
                        </div>

                        {{-- Bảng danh sách sản phẩm hiện tại của hãng --}}
                        <div class="table-responsive" style="max-height: 380px; overflow-y: auto; border: 1px solid #f1f5f9; border-radius: 10px;">
                            <table class="table table-hover align-middle mb-0" style="font-size: 13px;" id="brand-assigned-products-table">
                                <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 2;">
                                    <tr>
                                        <th style="width: 52px;">Ảnh</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Mã SKU</th>
                                        <th>Giá bán</th>
                                        <th style="text-align: right; width: 90px;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="brand-assigned-products-tbody">
                                    @forelse($initialProducts as $item)
                                        <tr id="assigned-product-row-{{ $item->id }}">
                                            <td>
                                                <img src="{{ $item->primaryImage?->url ? asset('clients/assets/img/clothes/' . $item->primaryImage->url) : asset('clients/assets/img/clothes/no-image.webp') }}" alt="{{ $item->name }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                                            </td>
                                            <td>
                                                <div style="font-weight: 600; color: #0f172a;">{{ $item->name }}</div>
                                            </td>
                                            <td><span style="font-family: monospace; color: #64748b;">{{ $item->sku ?: '--' }}</span></td>
                                            <td style="font-weight: 600; color: #059669;">
                                                {{ number_format((float) ($item->sale_price ?: $item->price), 0, ',', '.') }} ₫
                                            </td>
                                            <td style="text-align: right;">
                                                <button type="button" class="btn-modern btn-modern-danger btn-sm btn-detach-product" data-id="{{ $item->id }}" data-name="{{ $item->name }}" style="padding: 4px 10px; font-size: 11px;">
                                                    <i class="fa-solid fa-link-slash"></i> Gỡ
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="empty-assigned-row">
                                            <td colspan="5" style="text-align: center; padding: 32px 16px; color: #94a3b8;">
                                                <i class="fa-solid fa-box-open" style="font-size: 32px; margin-bottom: 8px; display: block; color: #cbd5e1;"></i>
                                                Chưa có sản phẩm nào thuộc thương hiệu này. Bấm nút <strong>"Thêm sản phẩm vào hãng"</strong> để chọn.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Hidden inputs khi tạo mới --}}
                        <div id="hidden-assigned-inputs"></div>
                    </div>
                </div>

                {{-- Card 3: Banner Hero Slider (Trình quản lý slide động) --}}
                <div class="modern-card">
                    <div class="card-header-styled">
                        <h3><i class="fa-solid fa-images"></i> Banner Hero Slider (Trình chiếu)</h3>
                        <button type="button" class="btn-modern btn-modern-secondary btn-sm" id="btn-add-slide">
                            <i class="fa-solid fa-plus text-primary"></i> Thêm slide
                        </button>
                    </div>
                    <div class="card-body-styled">
                        <div class="form-helper" style="margin-bottom: 16px;">
                            Các slide banner chữ lớn ấn tượng ở đầu trang gian hàng. Mỗi slide gồm thẻ Tag nhỏ, Tiêu đề chính, Từ khóa nổi bật và <strong>Ảnh nền slide</strong> (được lưu tại <code>clients/assets/img/brands</code>).
                        </div>

                        <div id="slides-container">
                            @php
                                $bannerSlides = old('banner_slides', $brand->banner_slides ?? []);
                                if (empty($bannerSlides)) {
                                    $bannerSlides = [
                                        ['tag' => '#NEW ARRIVALS', 'title' => 'Đột phá phong cách', 'highlight' => 'MỖI NGÀY', 'image' => null]
                                    ];
                                }
                            @endphp

                            @foreach($bannerSlides as $index => $slide)
                                @php
                                    $hasImg = !empty($slide['image']) && file_exists(public_path('clients/assets/img/brands/' . $slide['image']));
                                @endphp
                                <div class="repeater-item-card slide-item" data-index="{{ $index }}">
                                    <div class="repeater-item-header">
                                        <div class="repeater-item-title">
                                            <i class="fa-solid fa-film text-primary"></i>
                                            <span>Slide #<span class="slide-number">{{ $index + 1 }}</span></span>
                                        </div>
                                        <button type="button" class="btn-remove-item btn-remove-slide" title="Xóa slide này">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label-styled" style="font-size: 12px;">Tag phụ / Nhãn nhỏ</label>
                                            <input type="text" name="banner_slides[{{ $index }}][tag]" class="form-control-styled" value="{{ $slide['tag'] ?? '' }}" placeholder="VD: #SUMMER 2026">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label-styled" style="font-size: 12px;">Tiêu đề chính</label>
                                            <input type="text" name="banner_slides[{{ $index }}][title]" class="form-control-styled" value="{{ $slide['title'] ?? '' }}" placeholder="VD: Một bầu trời">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label-styled" style="font-size: 12px;">Từ khóa Highlight (Màu vàng)</label>
                                            <input type="text" name="banner_slides[{{ $index }}][highlight]" class="form-control-styled" value="{{ $slide['highlight'] ?? '' }}" placeholder="VD: NOBIFASHION">
                                        </div>
                                    </div>

                                    {{-- Khung upload và xem trước ảnh của slide --}}
                                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #e2e8f0; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                                        <div class="slide-thumb-box" style="width: 110px; height: 62px; border-radius: 8px; border: 1px solid #cbd5e1; background: #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                                            <img src="{{ $hasImg ? asset('clients/assets/img/brands/' . $slide['image']) : '' }}" alt="Slide Image" class="slide-thumb-preview" style="width: 100%; height: 100%; object-fit: cover; display: {{ $hasImg ? 'block' : 'none' }};">
                                            <span class="slide-thumb-empty" style="font-size: 11px; color: #94a3b8; display: {{ $hasImg ? 'none' : 'inline-flex' }}; align-items: center; gap: 4px;">
                                                <i class="fa-solid fa-image"></i> Chưa có ảnh
                                            </span>
                                        </div>
                                        <div style="flex: 1; min-width: 220px;">
                                            <label class="form-label-styled" style="font-size: 12px; margin-bottom: 3px;">Ảnh nền slide banner</label>
                                            <input type="file" name="banner_slides[{{ $index }}][image_file]" class="form-control-styled slide-file-input" accept="image/*" style="padding: 6px 10px; font-size: 12px;">
                                            <input type="hidden" name="banner_slides[{{ $index }}][image]" value="{{ $slide['image'] ?? '' }}" class="slide-img-hidden">
                                            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 3px;">
                                                <span class="form-helper" style="font-size: 11px; margin: 0;">Lưu vào <code>clients/assets/img/brands</code></span>
                                                @if(!empty($slide['image']))
                                                    <button type="button" class="btn-clear-slide-img" style="border: none; background: none; color: #ef4444; font-size: 11px; cursor: pointer; padding: 0;">
                                                        <i class="fa-solid fa-xmark"></i> Xóa ảnh hiện tại
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Card 4: Câu hỏi thường gặp FAQs (Trình quản lý FAQ động) --}}
                <div class="modern-card">
                    <div class="card-header-styled">
                        <h3><i class="fa-solid fa-circle-question"></i> Câu hỏi thường gặp (FAQs)</h3>
                        <button type="button" class="btn-modern btn-modern-secondary btn-sm" id="btn-add-faq">
                            <i class="fa-solid fa-plus text-primary"></i> Thêm câu hỏi
                        </button>
                    </div>
                    <div class="card-body-styled">
                        <div class="form-helper" style="margin-bottom: 16px;">
                            Bộ câu hỏi hiển thị dạng Accordion cuối trang gian hàng, đồng thời tự động đồng bộ vào cấu trúc <strong>Schema FAQPage của Google</strong> giúp tăng thứ hạng SEO.
                        </div>

                        <div id="faqs-container">
                            @php
                                $faqs = old('faqs', $brand->faqs ?? []);
                            @endphp

                            @forelse($faqs as $index => $faq)
                                <div class="repeater-item-card faq-item" data-index="{{ $index }}">
                                    <div class="repeater-item-header">
                                        <div class="repeater-item-title">
                                            <i class="fa-solid fa-question text-info"></i>
                                            <span>Câu hỏi #<span class="faq-number">{{ $index + 1 }}</span></span>
                                        </div>
                                        <button type="button" class="btn-remove-item btn-remove-faq" title="Xóa câu hỏi này">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label-styled" style="font-size: 12px;">Nội dung câu hỏi</label>
                                            <input type="text" name="faqs[{{ $index }}][question]" class="form-control-styled" value="{{ $faq['question'] ?? '' }}" placeholder="VD: Mua sản phẩm chính hãng ở đâu?">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label-styled" style="font-size: 12px;">Câu trả lời chi tiết</label>
                                            <textarea name="faqs[{{ $index }}][answer]" rows="2" class="form-control-styled" placeholder="Nhập câu trả lời cụ thể...">{{ $faq['answer'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="repeater-item-card faq-item" data-index="0">
                                    <div class="repeater-item-header">
                                        <div class="repeater-item-title">
                                            <i class="fa-solid fa-question text-info"></i>
                                            <span>Câu hỏi #<span class="faq-number">1</span></span>
                                        </div>
                                        <button type="button" class="btn-remove-item btn-remove-faq" title="Xóa câu hỏi này">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label-styled" style="font-size: 12px;">Nội dung câu hỏi</label>
                                            <input type="text" name="faqs[0][question]" class="form-control-styled" placeholder="VD: Mua sản phẩm chính hãng ở đâu?">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label-styled" style="font-size: 12px;">Câu trả lời chi tiết</label>
                                            <textarea name="faqs[0][answer]" rows="2" class="form-control-styled" placeholder="Nhập câu trả lời cụ thể..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Card 5: Tối ưu SEO Meta & Canonical --}}
                <div class="modern-card">
                    <div class="card-header-styled">
                        <h3><i class="fa-solid fa-magnifying-glass-chart"></i> Tối ưu SEO Meta & Canonical</h3>
                    </div>
                    <div class="card-body-styled">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label-styled">
                                    Meta Title (Tiêu đề SEO)
                                    <span class="char-counter" id="meta-title-counter">0 / 60 ký tự</span>
                                </label>
                                <input type="text" name="meta_title" id="meta-title-input" class="form-control-styled" value="{{ old('meta_title', $brand->meta_title) }}" placeholder="Ví dụ: NOBIFASHION - Thương Hiệu Thời Trang Nam Chính Hãng">
                                <div class="form-helper">Tiêu đề xuất hiện trên thẻ tab trình duyệt và kết quả tìm kiếm Google.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label-styled">Meta Canonical URL</label>
                                <input type="text" name="meta_canonical" class="form-control-styled" value="{{ old('meta_canonical', $brand->meta_canonical) }}" placeholder="https://nobifashion.vn/brand/nobifashion">
                                <div class="form-helper">Đường dẫn chuẩn SEO chống trùng lặp nội dung. Để trống nếu lấy mặc định.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label-styled">Meta Keywords</label>
                                <input type="text" name="meta_keywords" class="form-control-styled" value="{{ old('meta_keywords', $brand->meta_keywords) }}" placeholder="thời trang nam, áo polo nam, thương hiệu yody...">
                            </div>
                            <div class="col-12">
                                <label class="form-label-styled">
                                    Meta Description (Mô tả SEO)
                                    <span class="char-counter" id="meta-desc-counter">0 / 160 ký tự</span>
                                </label>
                                <textarea name="meta_description" id="meta-desc-input" rows="3" class="form-control-styled" placeholder="Nhập đoạn mô tả ngắn gọn và hấp dẫn khoảng 120-160 ký tự...">{{ old('meta_description', $brand->meta_description) }}</textarea>
                            </div>
                        </div>

                        {{-- Google Snippet Preview --}}
                        <div class="google-preview-card">
                            <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Xem trước trên Google</div>
                            <div class="google-preview-url">
                                <i class="fa-brands fa-google text-muted"></i>
                                <span>https://nobifashion.vn › brand › <strong id="preview-url-slug">{{ $brand->slug ?: 'thuong-hieu' }}</strong></span>
                            </div>
                            <div class="google-preview-title" id="preview-title-text">
                                {{ $brand->meta_title ?: ($brand->name ? $brand->name . ' - Thương Hiệu Thời Trang Chính Hãng' : 'Tiêu đề thương hiệu trên Google') }}
                            </div>
                            <p class="google-preview-desc" id="preview-desc-text">
                                {{ $brand->meta_description ?: ($brand->description ?: 'Khám phá bộ sưu tập thời trang cao cấp chính hãng với nhiều ưu đãi hấp dẫn tại NOBIFASHION...') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cột phải (4 cols): Trạng thái & Logo --}}
            <div class="col-lg-4">
                {{-- Card Xuất bản & Hiển thị --}}
                <div class="modern-card">
                    <div class="card-header-styled">
                        <h3><i class="fa-solid fa-sliders"></i> Xuất bản</h3>
                    </div>
                    <div class="card-body-styled">
                        <div class="mb-3">
                            <label class="form-label-styled">Trạng thái hoạt động</label>
                            <select name="is_active" class="form-control-styled">
                                <option value="1" {{ old('is_active', $brand->is_active ?? true) ? 'selected' : '' }}>🟢 Hiển thị công khai</option>
                                <option value="0" {{ old('is_active', $brand->is_active ?? true) ? '' : 'selected' }}>⚪ Tạm ẩn gian hàng</option>
                            </select>
                            <div class="form-helper">Khi ẩn, thương hiệu sẽ không xuất hiện trong danh mục và bộ lọc client.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-styled">Thứ tự hiển thị (Sort Order)</label>
                            <input type="number" name="sort_order" class="form-control-styled" value="{{ old('sort_order', $brand->sort_order ?? 0) }}" min="0">
                            <div class="form-helper">Số nhỏ hơn sẽ được ưu tiên hiển thị trước.</div>
                        </div>

                        <button type="submit" class="btn-modern btn-modern-primary w-100" style="justify-content: center;">
                            <i class="fa-solid fa-floppy-disk"></i> {{ $isEdit ? 'Cập nhật thay đổi' : 'Tạo thương hiệu' }}
                        </button>
                    </div>
                </div>

                {{-- Card Logo Thương hiệu (Instant Preview) --}}
                <div class="modern-card">
                    <div class="card-header-styled">
                        <h3><i class="fa-solid fa-image"></i> Logo thương hiệu</h3>
                    </div>
                    <div class="card-body-styled">
                        <div class="logo-preview-box" id="logo-preview-wrapper">
                            @if($brand->logo)
                                <img src="{{ asset(trim((string) config('media.directories.brands', 'clients/assets/img/brands'), '/') . '/' . $brand->logo) }}" alt="{{ $brand->name }}" id="logo-preview-img">
                            @else
                                <div id="logo-placeholder" style="display: flex; flex-direction: column; align-items: center; color: #94a3b8;">
                                    <i class="fa-solid fa-cloud-arrow-up" style="font-size: 32px; margin-bottom: 6px;"></i>
                                    <span style="font-size: 12px;">Chưa có logo</span>
                                </div>
                                <img src="" alt="Preview" id="logo-preview-img" style="display: none;">
                            @endif
                        </div>

                        <div class="logo-upload-wrapper">
                            <input type="file" name="logo" id="logo-file-input" class="logo-upload-input" accept="image/*">
                            <div style="font-size: 13.5px; font-weight: 600; color: #2563eb; margin-bottom: 2px;">
                                <i class="fa-solid fa-upload"></i> Chọn hoặc kéo thả ảnh logo
                            </div>
                            <div style="font-size: 12px; color: #64748b;">PNG, JPG, WEBP (Tối đa 4MB)</div>
                        </div>
                        <div class="form-helper" style="text-align: center; margin-top: 8px;">
                            Khuyến nghị ảnh vuông tỉ lệ 1:1, nền trong suốt hoặc trắng (400x400px).
                        </div>
                    </div>
                </div>

                {{-- Nếu đang sửa: Card Liên kết nhanh --}}
                @if($isEdit)
                    <div class="modern-card">
                        <div class="card-header-styled">
                            <h3><i class="fa-solid fa-link"></i> Liên kết nhanh</h3>
                        </div>
                        <div class="card-body-styled">
                            <div style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                                Truy cập nhanh gian hàng trực tiếp trên website để kiểm tra giao diện hiển thị:
                            </div>
                            <a href="{{ route('client.brand.show', $brand->slug) }}" target="_blank" class="btn-modern btn-modern-secondary w-100" style="justify-content: center;">
                                <i class="fa-solid fa-eye text-primary"></i> Xem gian hàng ngoài web
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </form>

    {{-- Modal Tìm kiếm & Chọn sản phẩm vào thương hiệu --}}
    <div class="modal fade" id="attachProductsModal" tabindex="-1" aria-labelledby="attachProductsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
                <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 16px 24px;">
                    <h5 class="modal-title" id="attachProductsModalLabel" style="font-size: 16px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-shirt text-primary"></i> Thêm sản phẩm vào thương hiệu: <span class="text-primary">{{ $brand->name ?: 'Mới' }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 20px 24px;">
                    {{-- Thanh tìm kiếm & lọc nhanh --}}
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <div class="search-box" style="position: relative;">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                                <input type="text" id="modal-product-search" class="form-control-styled" placeholder="Nhập tên sản phẩm hoặc mã SKU (Tìm kiếm tức thì)..." style="padding-left: 38px;">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <select id="modal-category-filter" class="form-control-styled">
                                <option value="">Tất cả danh mục sản phẩm</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Toolbar đếm & chọn tất cả --}}
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; font-size: 13px; color: #64748b;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="modal-select-all" style="cursor: pointer; width: 16px; height: 16px;">
                            <label for="modal-select-all" style="cursor: pointer; font-weight: 600; margin: 0; color: #334155;">Chọn tất cả trên trang này</label>
                        </div>
                        <div>
                            Đã chọn: <strong id="modal-selected-count" style="color: #2563eb; font-size: 14px;">0</strong> sản phẩm
                        </div>
                    </div>

                    {{-- Bảng kết quả tìm kiếm --}}
                    <div class="table-responsive" style="min-height: 280px; max-height: 380px; overflow-y: auto; border: 1px solid #f1f5f9; border-radius: 10px;">
                        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                            <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 2;">
                                <tr>
                                    <th style="width: 40px; text-align: center;"></th>
                                    <th style="width: 50px;">Ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Mã SKU</th>
                                    <th>Giá</th>
                                    <th>Thương hiệu hiện tại</th>
                                    <th style="text-align: right; width: 110px;"></th>
                                </tr>
                            </thead>
                            <tbody id="modal-products-tbody">
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div> Đang tải danh sách sản phẩm...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Phân trang trong modal --}}
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 14px;" id="modal-pagination-box">
                        <div style="font-size: 12.5px; color: #64748b;" id="modal-pagination-info">
                            Đang tải...
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn-modern btn-modern-secondary btn-sm" id="btn-modal-prev-page" disabled>
                                <i class="fa-solid fa-chevron-left"></i> Trước
                            </button>
                            <button type="button" class="btn-modern btn-modern-secondary btn-sm" id="btn-modal-next-page" disabled>
                                Sau <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 24px;">
                    <button type="button" class="btn-modern btn-modern-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn-modern btn-modern-primary" id="btn-modal-confirm-attach">
                        <i class="fa-solid fa-plus"></i> <span id="btn-modal-confirm-text">Thêm sản phẩm đã chọn</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Tự động sinh slug nếu chưa có
            const nameInput = document.getElementById('brand-name-input');
            const slugInput = document.getElementById('brand-slug-input');
            const previewSlug = document.getElementById('preview-url-slug');

            function convertToSlug(text) {
                return text.toString().toLowerCase().trim()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[đĐ]/g, 'd')
                    .replace(/[^a-z0-9 -]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
            }

            if (nameInput && slugInput) {
                nameInput.addEventListener('input', () => {
                    if (!slugInput.dataset.manual) {
                        const slug = convertToSlug(nameInput.value);
                        slugInput.value = slug;
                        if (previewSlug) previewSlug.textContent = slug || 'thuong-hieu';
                    }
                    updateGooglePreview();
                });

                slugInput.addEventListener('input', () => {
                    slugInput.dataset.manual = 'true';
                    if (previewSlug) previewSlug.textContent = slugInput.value || 'thuong-hieu';
                });
            }

            // 2. Instant Image Preview (Xem trước logo ngay khi chọn file)
            const logoInput = document.getElementById('logo-file-input');
            const logoImg = document.getElementById('logo-preview-img');
            const logoPlaceholder = document.getElementById('logo-placeholder');

            if (logoInput) {
                logoInput.addEventListener('change', function () {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            if (logoImg) {
                                logoImg.src = e.target.result;
                                logoImg.style.display = 'block';
                            }
                            if (logoPlaceholder) {
                                logoPlaceholder.style.display = 'none';
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // 3. Dynamic Repeater cho Banner Hero Slides
            const slidesContainer = document.getElementById('slides-container');
            const btnAddSlide = document.getElementById('btn-add-slide');

            function reindexSlides() {
                const items = slidesContainer.querySelectorAll('.slide-item');
                items.forEach((item, index) => {
                    item.dataset.index = index;
                    const numSpan = item.querySelector('.slide-number');
                    if (numSpan) numSpan.textContent = index + 1;

                    item.querySelectorAll('input').forEach(input => {
                        const name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/banner_slides\[\d+\]/, `banner_slides[${index}]`));
                        }
                    });
                });
            }

            if (btnAddSlide && slidesContainer) {
                btnAddSlide.addEventListener('click', () => {
                    const currentCount = slidesContainer.querySelectorAll('.slide-item').length;
                    const newSlideHtml = `
                        <div class="repeater-item-card slide-item" data-index="${currentCount}">
                            <div class="repeater-item-header">
                                <div class="repeater-item-title">
                                    <i class="fa-solid fa-film text-primary"></i>
                                    <span>Slide #<span class="slide-number">${currentCount + 1}</span></span>
                                </div>
                                <button type="button" class="btn-remove-item btn-remove-slide" title="Xóa slide này">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label-styled" style="font-size: 12px;">Tag phụ / Nhãn nhỏ</label>
                                    <input type="text" name="banner_slides[${currentCount}][tag]" class="form-control-styled" placeholder="VD: #SUMMER 2026">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-styled" style="font-size: 12px;">Tiêu đề chính</label>
                                    <input type="text" name="banner_slides[${currentCount}][title]" class="form-control-styled" placeholder="VD: Một bầu trời">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-styled" style="font-size: 12px;">Từ khóa Highlight (Màu vàng)</label>
                                    <input type="text" name="banner_slides[${currentCount}][highlight]" class="form-control-styled" placeholder="VD: NOBIFASHION">
                                </div>
                            </div>
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #e2e8f0; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                                <div class="slide-thumb-box" style="width: 110px; height: 62px; border-radius: 8px; border: 1px solid #cbd5e1; background: #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                                    <img src="" alt="Slide Image" class="slide-thumb-preview" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                    <span class="slide-thumb-empty" style="font-size: 11px; color: #94a3b8; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-image"></i> Chưa có ảnh
                                    </span>
                                </div>
                                <div style="flex: 1; min-width: 220px;">
                                    <label class="form-label-styled" style="font-size: 12px; margin-bottom: 3px;">Ảnh nền slide banner</label>
                                    <input type="file" name="banner_slides[${currentCount}][image_file]" class="form-control-styled slide-file-input" accept="image/*" style="padding: 6px 10px; font-size: 12px;">
                                    <input type="hidden" name="banner_slides[${currentCount}][image]" value="" class="slide-img-hidden">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 3px;">
                                        <span class="form-helper" style="font-size: 11px; margin: 0;">Lưu vào <code>clients/assets/img/brands</code></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    slidesContainer.insertAdjacentHTML('beforeend', newSlideHtml);
                    reindexSlides();
                });

                // Xóa slide
                slidesContainer.addEventListener('click', (e) => {
                    const removeBtn = e.target.closest('.btn-remove-slide');
                    if (removeBtn) {
                        const item = removeBtn.closest('.slide-item');
                        if (item) {
                            item.remove();
                            reindexSlides();
                        }
                    }

                    // Xóa ảnh slide hiện tại
                    const clearImgBtn = e.target.closest('.btn-clear-slide-img');
                    if (clearImgBtn) {
                        const slideItem = clearImgBtn.closest('.slide-item');
                        if (slideItem) {
                            const hiddenInput = slideItem.querySelector('.slide-img-hidden');
                            const fileInput = slideItem.querySelector('.slide-file-input');
                            const previewImg = slideItem.querySelector('.slide-thumb-preview');
                            const emptySpan = slideItem.querySelector('.slide-thumb-empty');

                            if (hiddenInput) hiddenInput.value = '';
                            if (fileInput) fileInput.value = '';
                            if (previewImg) {
                                previewImg.src = '';
                                previewImg.style.display = 'none';
                            }
                            if (emptySpan) emptySpan.style.display = 'inline-flex';
                            clearImgBtn.style.display = 'none';
                        }
                    }
                });

                // Instant Preview khi chọn file ảnh cho slide
                slidesContainer.addEventListener('change', (e) => {
                    if (e.target.classList.contains('slide-file-input')) {
                        const file = e.target.files[0];
                        const slideItem = e.target.closest('.slide-item');
                        if (file && slideItem) {
                            const previewImg = slideItem.querySelector('.slide-thumb-preview');
                            const emptySpan = slideItem.querySelector('.slide-thumb-empty');

                            const reader = new FileReader();
                            reader.onload = function (evt) {
                                if (previewImg) {
                                    previewImg.src = evt.target.result;
                                    previewImg.style.display = 'block';
                                }
                                if (emptySpan) {
                                    emptySpan.style.display = 'none';
                                }
                            };
                            reader.readAsDataURL(file);
                        }
                    }
                });
            }

            // 4. Dynamic Repeater cho FAQs
            const faqsContainer = document.getElementById('faqs-container');
            const btnAddFaq = document.getElementById('btn-add-faq');

            function reindexFaqs() {
                const items = faqsContainer.querySelectorAll('.faq-item');
                items.forEach((item, index) => {
                    item.dataset.index = index;
                    const numSpan = item.querySelector('.faq-number');
                    if (numSpan) numSpan.textContent = index + 1;

                    item.querySelectorAll('input, textarea').forEach(input => {
                        const name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/faqs\[\d+\]/, `faqs[${index}]`));
                        }
                    });
                });
            }

            if (btnAddFaq && faqsContainer) {
                btnAddFaq.addEventListener('click', () => {
                    const currentCount = faqsContainer.querySelectorAll('.faq-item').length;
                    const newFaqHtml = `
                        <div class="repeater-item-card faq-item" data-index="${currentCount}">
                            <div class="repeater-item-header">
                                <div class="repeater-item-title">
                                    <i class="fa-solid fa-question text-info"></i>
                                    <span>Câu hỏi #<span class="faq-number">${currentCount + 1}</span></span>
                                </div>
                                <button type="button" class="btn-remove-item btn-remove-faq" title="Xóa câu hỏi này">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label-styled" style="font-size: 12px;">Nội dung câu hỏi</label>
                                    <input type="text" name="faqs[${currentCount}][question]" class="form-control-styled" placeholder="VD: Mua sản phẩm chính hãng ở đâu?">
                                </div>
                                <div class="col-12">
                                    <label class="form-label-styled" style="font-size: 12px;">Câu trả lời chi tiết</label>
                                    <textarea name="faqs[${currentCount}][answer]" rows="2" class="form-control-styled" placeholder="Nhập câu trả lời cụ thể..."></textarea>
                                </div>
                            </div>
                        </div>
                    `;
                    faqsContainer.insertAdjacentHTML('beforeend', newFaqHtml);
                    reindexFaqs();
                });

                faqsContainer.addEventListener('click', (e) => {
                    const removeBtn = e.target.closest('.btn-remove-faq');
                    if (removeBtn) {
                        const item = removeBtn.closest('.faq-item');
                        if (item) {
                            item.remove();
                            reindexFaqs();
                        }
                    }
                });
            }

            // 5. Đếm ký tự SEO & Cập nhật Google Preview
            const metaTitleInput = document.getElementById('meta-title-input');
            const metaDescInput = document.getElementById('meta-desc-input');
            const metaTitleCounter = document.getElementById('meta-title-counter');
            const metaDescCounter = document.getElementById('meta-desc-counter');
            const previewTitle = document.getElementById('preview-title-text');
            const previewDesc = document.getElementById('preview-desc-text');

            function updateGooglePreview() {
                const titleVal = metaTitleInput?.value.trim() || (nameInput?.value.trim() ? nameInput.value.trim() + ' - Thương Hiệu Thời Trang' : 'Tiêu đề thương hiệu trên Google');
                const descVal = metaDescInput?.value.trim() || 'Khám phá bộ sưu tập thời trang cao cấp chính hãng với nhiều ưu đãi hấp dẫn tại NOBIFASHION...';

                if (previewTitle) previewTitle.textContent = titleVal;
                if (previewDesc) previewDesc.textContent = descVal;

                if (metaTitleCounter && metaTitleInput) {
                    metaTitleCounter.textContent = `${metaTitleInput.value.length} / 60 ký tự`;
                    metaTitleCounter.style.color = metaTitleInput.value.length > 60 ? '#ef4444' : '#94a3b8';
                }
                if (metaDescCounter && metaDescInput) {
                    metaDescCounter.textContent = `${metaDescInput.value.length} / 160 ký tự`;
                    metaDescCounter.style.color = metaDescInput.value.length > 160 ? '#ef4444' : '#94a3b8';
                }
            }

            if (metaTitleInput) metaTitleInput.addEventListener('input', updateGooglePreview);
            if (metaDescInput) metaDescInput.addEventListener('input', updateGooglePreview);
            updateGooglePreview();

            // 6. Quản lý Sản phẩm thuộc thương hiệu & Popup Modal tìm kiếm cực nhanh
            const isEdit = {{ $isEdit ? 'true' : 'false' }};
            const brandId = {{ $brand->id ?? 0 }};
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            const searchProductsUrl = "{{ route('admin.brands.search-products') }}";
            const attachProductsUrl = "{{ $isEdit ? route('admin.brands.attach-products', $brand->id ?? 0) : '' }}";
            const detachProductUrl = "{{ $isEdit ? route('admin.brands.detach-product', $brand->id ?? 0) : '' }}";

            // Modal elements
            const modalEl = document.getElementById('attachProductsModal');
            const attachModal = modalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal(modalEl) : null;
            const btnOpenModal = document.getElementById('btn-open-attach-modal');
            const searchInput = document.getElementById('modal-product-search');
            const catFilter = document.getElementById('modal-category-filter');
            const modalTbody = document.getElementById('modal-products-tbody');
            const selectAllCheckbox = document.getElementById('modal-select-all');
            const selectedCountSpan = document.getElementById('modal-selected-count');
            const btnConfirmAttach = document.getElementById('btn-modal-confirm-attach');
            const btnPrevPage = document.getElementById('btn-modal-prev-page');
            const btnNextPage = document.getElementById('btn-modal-next-page');
            const paginationInfo = document.getElementById('modal-pagination-info');

            // Bảng sản phẩm hiện tại của Brand
            const brandTbody = document.getElementById('brand-assigned-products-tbody');
            const brandCountBadge = document.getElementById('brand-products-count-badge');
            const hiddenInputsBox = document.getElementById('hidden-assigned-inputs');

            // Set quản lý ID
            const assignedProductIds = new Set();
            document.querySelectorAll('#brand-assigned-products-tbody .btn-detach-product').forEach(btn => {
                const id = parseInt(btn.getAttribute('data-id'), 10);
                if (id) assignedProductIds.add(id);
            });

            // Map cache dữ liệu sản phẩm trong modal để render nhanh
            const modalProductsCache = new Map();
            const selectedProductIds = new Set();

            let currentModalPage = 1;
            let lastModalPage = 1;
            let searchAbortController = null;
            let debounceTimer = null;

            // Toast notification gọn nhẹ
            function showToast(message, type = 'success') {
                let toastBox = document.getElementById('brand-admin-toast-container');
                if (!toastBox) {
                    toastBox = document.createElement('div');
                    toastBox.id = 'brand-admin-toast-container';
                    toastBox.style.cssText = 'position: fixed; top: 24px; right: 24px; z-index: 1060; display: flex; flex-direction: column; gap: 10px; pointer-events: none;';
                    document.body.appendChild(toastBox);
                }

                const toast = document.createElement('div');
                const isErr = type === 'error';
                toast.style.cssText = `
                    background: ${isErr ? '#ef4444' : '#059669'};
                    color: #ffffff;
                    padding: 12px 20px;
                    border-radius: 10px;
                    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
                    font-size: 13.5px;
                    font-weight: 500;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    pointer-events: auto;
                    opacity: 0;
                    transform: translateY(-10px);
                    transition: all 0.25s ease;
                `;
                toast.innerHTML = `<i class="fa-solid ${isErr ? 'fa-circle-xmark' : 'fa-circle-check'}" style="font-size: 16px;"></i> <span>${message}</span>`;
                toastBox.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0)';
                });

                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-10px)';
                    setTimeout(() => toast.remove(), 250);
                }, 3000);
            }

            // Cập nhật số lượng sản phẩm trên badge
            function updateBrandCountBadge() {
                if (brandCountBadge) {
                    const count = assignedProductIds.size;
                    brandCountBadge.textContent = `${count} sản phẩm`;
                }
            }

            // Render 1 hàng sản phẩm vào bảng của Brand
            function appendProductToBrandTable(product) {
                const emptyRow = document.getElementById('empty-assigned-row');
                if (emptyRow) emptyRow.remove();

                const existingRow = document.getElementById(`assigned-product-row-${product.id}`);
                if (existingRow) return;

                const priceFormatted = new Intl.NumberFormat('vi-VN').format(product.sale_price || product.price || 0) + ' ₫';
                const row = document.createElement('tr');
                row.id = `assigned-product-row-${product.id}`;
                row.style.animation = 'fadeIn 0.3s ease';
                row.innerHTML = `
                    <td>
                        <img src="${product.image || '/clients/assets/img/clothes/no-image.webp'}" alt="${product.name}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #0f172a;">${product.name}</div>
                    </td>
                    <td><span style="font-family: monospace; color: #64748b;">${product.sku || '--'}</span></td>
                    <td style="font-weight: 600; color: #059669;">${priceFormatted}</td>
                    <td style="text-align: right;">
                        <button type="button" class="btn-modern btn-modern-danger btn-sm btn-detach-product" data-id="${product.id}" data-name="${product.name}" style="padding: 4px 10px; font-size: 11px;">
                            <i class="fa-solid fa-link-slash"></i> Gỡ
                        </button>
                    </td>
                `;
                brandTbody.appendChild(row);
            }

            // Gọi API nạp danh sách sản phẩm trong Modal
            function fetchModalProducts(page = 1) {
                if (searchAbortController) {
                    searchAbortController.abort();
                }
                searchAbortController = new AbortController();

                currentModalPage = page;
                const keyword = (searchInput?.value || '').trim();
                const categoryId = catFilter?.value || '';

                modalTbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                            <div class="spinner-border spinner-border-sm text-primary" role="status" style="margin-right: 6px;"></div>
                            Đang tải danh sách sản phẩm...
                        </td>
                    </tr>
                `;

                const url = new URL(searchProductsUrl, window.location.origin);
                url.searchParams.set('page', page);
                if (keyword) url.searchParams.set('keyword', keyword);
                if (categoryId) url.searchParams.set('category_id', categoryId);
                if (brandId) url.searchParams.set('brand_id', brandId);

                fetch(url.toString(), {
                    signal: searchAbortController.signal,
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        modalTbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #ef4444; padding: 30px;">Không thể tải dữ liệu: ${data.message || 'Lỗi'}</td></tr>`;
                        return;
                    }

                    const products = data.data || [];
                    lastModalPage = data.last_page || 1;

                    // Lưu vào cache để thao tác nhanh
                    products.forEach(p => modalProductsCache.set(p.id, p));

                    if (products.length === 0) {
                        modalTbody.innerHTML = `
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                                    <i class="fa-solid fa-magnifying-glass" style="font-size: 26px; margin-bottom: 8px; display: block; color: #cbd5e1;"></i>
                                    Không tìm thấy sản phẩm nào phù hợp với bộ lọc tìm kiếm.
                                </td>
                            </tr>
                        `;
                    } else {
                        let html = '';
                        products.forEach(p => {
                            const isAssigned = assignedProductIds.has(p.id);
                            const isChecked = selectedProductIds.has(p.id);
                            const priceFormatted = new Intl.NumberFormat('vi-VN').format(p.price || 0) + ' ₫';
                            
                            let brandBadge = '<span class="badge" style="background: #f1f5f9; color: #64748b; font-weight: normal;">Chưa có</span>';
                            if (p.brand_name) {
                                if (brandId && p.brand_id === brandId) {
                                    brandBadge = `<span class="badge" style="background: #dcfce7; color: #15803d; font-weight: 600;"><i class="fa-solid fa-check"></i> Hãng này</span>`;
                                } else {
                                    brandBadge = `<span class="badge" style="background: #fef3c7; color: #b45309; font-weight: 500;" title="Đang thuộc ${p.brand_name}">${p.brand_name}</span>`;
                                }
                            }

                            html += `
                                <tr class="${isAssigned ? 'table-light' : ''}">
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="modal-product-chk" data-id="${p.id}" ${isChecked ? 'checked' : ''} ${isAssigned ? 'disabled' : ''} style="cursor: pointer; width: 16px; height: 16px;">
                                    </td>
                                    <td>
                                        <img src="${p.image || '/clients/assets/img/clothes/no-image.webp'}" alt="${p.name}" style="width: 38px; height: 38px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #0f172a; max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${p.name}">
                                            ${p.name}
                                        </div>
                                    </td>
                                    <td><span style="font-family: monospace; color: #64748b;">${p.sku || '--'}</span></td>
                                    <td style="font-weight: 600; color: #059669;">${priceFormatted}</td>
                                    <td>${brandBadge}</td>
                                    <td style="text-align: right;">
                                        ${isAssigned ? `
                                            <button type="button" class="btn btn-sm btn-outline-secondary disabled" style="font-size: 11px; padding: 3px 8px; border-radius: 6px;">
                                                <i class="fa-solid fa-check"></i> Đã có
                                            </button>
                                        ` : `
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-modal-quick-add" data-id="${p.id}" style="font-size: 11px; padding: 3px 10px; border-radius: 6px; font-weight: 600;">
                                                <i class="fa-solid fa-plus"></i> Thêm
                                            </button>
                                        `}
                                    </td>
                                </tr>
                            `;
                        });
                        modalTbody.innerHTML = html;
                    }

                    // Cập nhật phân trang
                    if (paginationInfo) {
                        paginationInfo.innerHTML = `Hiển thị trang <strong>${data.current_page}</strong> / <strong>${data.last_page}</strong> (Tổng <strong>${data.total}</strong> sản phẩm)`;
                    }
                    if (btnPrevPage) btnPrevPage.disabled = data.current_page <= 1;
                    if (btnNextPage) btnNextPage.disabled = data.current_page >= data.last_page;

                    // Sync select all checkbox
                    syncSelectAllState();
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Fetch modal products error:', err);
                        modalTbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #ef4444; padding: 30px;">Lỗi tải dữ liệu. Vui lòng thử lại.</td></tr>`;
                    }
                });
            }

            // Đồng bộ trạng thái checkbox "Chọn tất cả"
            function syncSelectAllState() {
                const availableChks = modalTbody.querySelectorAll('.modal-product-chk:not(:disabled)');
                if (availableChks.length === 0) {
                    if (selectAllCheckbox) selectAllCheckbox.checked = false;
                    return;
                }
                const checkedCount = Array.from(availableChks).filter(c => c.checked).length;
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = checkedCount === availableChks.length;
                }
            }

            // Cập nhật số lượng sản phẩm được chọn
            function updateSelectedCountDisplay() {
                if (selectedCountSpan) {
                    selectedCountSpan.textContent = selectedProductIds.size;
                }
            }

            // Gán sản phẩm vào Brand (hỗ trợ cả Edit AJAX lẫn Create Form)
            function attachProducts(productIdsArray) {
                if (!productIdsArray || productIdsArray.length === 0) return;

                if (isEdit) {
                    btnConfirmAttach.disabled = true;
                    btnConfirmAttach.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Đang xử lý...';

                    fetch(attachProductsUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ product_ids: productIdsArray })
                    })
                    .then(res => res.json())
                    .then(data => {
                        btnConfirmAttach.disabled = false;
                        btnConfirmAttach.innerHTML = '<i class="fa-solid fa-plus"></i> <span id="btn-modal-confirm-text">Thêm sản phẩm đã chọn</span>';

                        if (data.success) {
                            productIdsArray.forEach(id => {
                                assignedProductIds.add(id);
                                selectedProductIds.delete(id);
                                const p = modalProductsCache.get(id);
                                if (p) appendProductToBrandTable(p);
                            });

                            updateBrandCountBadge();
                            updateSelectedCountDisplay();
                            showToast(data.message || 'Gán sản phẩm vào thương hiệu thành công!');

                            // Tự động đóng modal
                            if (attachModal) attachModal.hide();
                        } else {
                            showToast(data.message || 'Thao tác không thành công', 'error');
                        }
                    })
                    .catch(err => {
                        btnConfirmAttach.disabled = false;
                        btnConfirmAttach.innerHTML = '<i class="fa-solid fa-plus"></i> <span id="btn-modal-confirm-text">Thêm sản phẩm đã chọn</span>';
                        console.error('Attach error:', err);
                        showToast('Lỗi mạng khi gán sản phẩm', 'error');
                    });
                } else {
                    // Chế độ Create: lưu vào hidden inputs và vẽ bảng
                    productIdsArray.forEach(id => {
                        assignedProductIds.add(id);
                        selectedProductIds.delete(id);

                        if (hiddenInputsBox && !hiddenInputsBox.querySelector(`input[value="${id}"]`)) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'assigned_product_ids[]';
                            input.value = id;
                            input.id = `hidden-product-${id}`;
                            hiddenInputsBox.appendChild(input);
                        }

                        const p = modalProductsCache.get(id);
                        if (p) appendProductToBrandTable(p);
                    });

                    updateBrandCountBadge();
                    updateSelectedCountDisplay();
                    showToast(`Đã chọn ${productIdsArray.length} sản phẩm vào thương hiệu!`);
                    if (attachModal) attachModal.hide();
                }
            }

            // Gỡ sản phẩm khỏi Brand
            function detachProduct(productId, productName) {
                if (!confirm(`Bạn có chắc muốn gỡ sản phẩm "${productName || ''}" khỏi thương hiệu này?`)) {
                    return;
                }

                if (isEdit) {
                    fetch(detachProductUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ product_id: productId })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            assignedProductIds.delete(productId);
                            const row = document.getElementById(`assigned-product-row-${productId}`);
                            if (row) {
                                row.style.transition = 'all 0.25s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(20px)';
                                setTimeout(() => {
                                    row.remove();
                                    checkEmptyBrandTable();
                                }, 250);
                            }
                            updateBrandCountBadge();
                            showToast(data.message || 'Đã gỡ sản phẩm khỏi thương hiệu');
                        } else {
                            showToast(data.message || 'Lỗi khi gỡ sản phẩm', 'error');
                        }
                    })
                    .catch(err => {
                        console.error('Detach error:', err);
                        showToast('Lỗi mạng khi gỡ sản phẩm', 'error');
                    });
                } else {
                    // Create mode: xóa hidden input
                    assignedProductIds.delete(productId);
                    const hiddenInput = document.getElementById(`hidden-product-${productId}`);
                    if (hiddenInput) hiddenInput.remove();

                    const row = document.getElementById(`assigned-product-row-${productId}`);
                    if (row) {
                        row.remove();
                        checkEmptyBrandTable();
                    }
                    updateBrandCountBadge();
                    showToast('Đã gỡ sản phẩm khỏi danh sách chọn');
                }
            }

            function checkEmptyBrandTable() {
                if (assignedProductIds.size === 0 && brandTbody) {
                    brandTbody.innerHTML = `
                        <tr id="empty-assigned-row">
                            <td colspan="5" style="text-align: center; padding: 32px 16px; color: #94a3b8;">
                                <i class="fa-solid fa-box-open" style="font-size: 32px; margin-bottom: 8px; display: block; color: #cbd5e1;"></i>
                                Chưa có sản phẩm nào thuộc thương hiệu này. Bấm nút <strong>"Thêm sản phẩm vào hãng"</strong> để chọn.
                            </td>
                        </tr>
                    `;
                }
            }

            // Event Listeners cho Modal & Tìm kiếm
            if (btnOpenModal) {
                btnOpenModal.addEventListener('click', () => {
                    selectedProductIds.clear();
                    updateSelectedCountDisplay();
                    if (selectAllCheckbox) selectAllCheckbox.checked = false;
                    if (searchInput) searchInput.value = '';
                    if (catFilter) catFilter.value = '';

                    if (attachModal) attachModal.show();
                    setTimeout(() => {
                        if (searchInput) searchInput.focus();
                    }, 400);

                    fetchModalProducts(1);
                });
            }

            // Tìm kiếm với Debounce (250ms cực nhạy)
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        fetchModalProducts(1);
                    }, 250);
                });
            }

            // Lọc danh mục
            if (catFilter) {
                catFilter.addEventListener('change', () => {
                    fetchModalProducts(1);
                });
            }

            // Phân trang modal
            if (btnPrevPage) {
                btnPrevPage.addEventListener('click', () => {
                    if (currentModalPage > 1) fetchModalProducts(currentModalPage - 1);
                });
            }
            if (btnNextPage) {
                btnNextPage.addEventListener('click', () => {
                    if (currentModalPage < lastModalPage) fetchModalProducts(currentModalPage + 1);
                });
            }

            // Checkbox chọn tất cả trên trang
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', () => {
                    const isChecked = selectAllCheckbox.checked;
                    modalTbody.querySelectorAll('.modal-product-chk:not(:disabled)').forEach(chk => {
                        chk.checked = isChecked;
                        const id = parseInt(chk.getAttribute('data-id'), 10);
                        if (isChecked) selectedProductIds.add(id);
                        else selectedProductIds.delete(id);
                    });
                    updateSelectedCountDisplay();
                });
            }

            // Checkbox từng sản phẩm & Thêm nhanh (Event delegation)
            if (modalTbody) {
                modalTbody.addEventListener('click', (e) => {
                    // Bấm nút thêm nhanh
                    const quickAddBtn = e.target.closest('.btn-modal-quick-add');
                    if (quickAddBtn) {
                        const id = parseInt(quickAddBtn.getAttribute('data-id'), 10);
                        if (id) {
                            attachProducts([id]);
                        }
                        return;
                    }

                    // Tích checkbox
                    const chk = e.target.closest('.modal-product-chk');
                    if (chk) {
                        const id = parseInt(chk.getAttribute('data-id'), 10);
                        if (chk.checked) selectedProductIds.add(id);
                        else selectedProductIds.delete(id);
                        updateSelectedCountDisplay();
                        syncSelectAllState();
                    }
                });
            }

            // Bấm nút "Thêm các sản phẩm đã chọn"
            if (btnConfirmAttach) {
                btnConfirmAttach.addEventListener('click', () => {
                    if (selectedProductIds.size === 0) {
                        showToast('Vui lòng tích chọn ít nhất 1 sản phẩm để thêm', 'error');
                        return;
                    }
                    attachProducts(Array.from(selectedProductIds));
                });
            }

            // Nút gỡ sản phẩm trên bảng Brand (Event delegation)
            if (brandTbody) {
                brandTbody.addEventListener('click', (e) => {
                    const detachBtn = e.target.closest('.btn-detach-product');
                    if (detachBtn) {
                        const id = parseInt(detachBtn.getAttribute('data-id'), 10);
                        const name = detachBtn.getAttribute('data-name');
                        if (id) {
                            detachProduct(id, name);
                        }
                    }
                });
            }
        });
    </script>
@endpush
