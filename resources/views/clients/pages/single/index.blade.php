@extends('clients.layouts.master')

@section('title',
    renderMeta(
    $product->meta_title ??
    ($product->name ??
    'NOBI FASHION - Shop quần áo & phụ kiện thời
    trang giá ưu đãi [NOBI]currentyear[NOBI]'),
    ) .
    ' – ' .
    ($settings->site_name ?? 'NOBI FASHION'))

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/single.css?v=' . time()) }}">
    @if ($product?->primaryImage?->url)
        <link rel="preload" as="image"
            href="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
            fetchpriority="high">
    @else
        <link rel="preload" as="image" href="{{ asset('clients/assets/img/clothes/no-image.webp') }}"
            fetchpriority="high">
    @endif

    <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large"/>
    <meta name="keywords" content="{{ is_array($product->meta_keywords ?? null) ? implode(', ', $product->meta_keywords) : 'quần áo, phụ kiện, thời trang nam, thời trang nữ, áo phông, sơ mi, quần jean, váy, mũ nón, thắt lưng, NOBI FASHION' }}">

    <meta name="description"
        content="{{ renderMeta($product->meta_description ?? ($product->meta_title ?? ($product->name ?? 'Cửa hàng thời trang NOBI FASHION: quần áo & phụ kiện chính hãng, chất liệu đẹp, form chuẩn, giao nhanh 1–3 ngày, đổi size 7 ngày.'))) }}">

    <meta http-equiv="date" content="{{ \Carbon\Carbon::parse('2025-06-11 13:10:59')->format('d/m/y') }}" />

    <meta property="og:title"
        content="{{ renderMeta($product->meta_title ?? ($product->name ?? 'NOBI FASHION - Quần áo & phụ kiện thời trang')) }}">
    <meta property="og:description"
        content="{{ renderMeta($product->meta_description ?? 'NOBI FASHION: Mua sắm quần áo & phụ kiện thời trang nam nữ. Hàng mới, giá tốt, giao nhanh 1–3 ngày, đổi size 7 ngày.') }}">
    <meta property="og:url"
        content="{{ $product->canonical_url ?? ($settings->site_url ?? 'https://nobifashion.vn') . '/san-pham/' . ($product->slug ?? '') }}">
    <meta property="og:image"
        content="{{ asset('clients/assets/img/business/' . ($settings->site_banner ?? null ? $settings->site_banner : $settings->site_logo ?? 'logo-nobi-fashion.png')) }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt"
        content="{{ $product->primaryImage->title ?? null ?: $product->name ?? ($settings->site_name ?? 'NOBI FASHION') }}">
    <meta property="og:image:type" content="image/webp">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta property="og:locale" content="vi_VN">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="{{ $settings->site_name ?? 'NOBI FASHION' }}">
    <meta name="twitter:title"
        content="{{ renderMeta($product->meta_title ?? ($product->name ?? 'NOBI FASHION - Quần áo & phụ kiện thời trang')) }}">
    <meta name="twitter:description"
        content="{{ renderMeta($product->meta_description ?? 'NOBI FASHION: Mua sắm quần áo & phụ kiện thời trang nam nữ. Hàng mới, giá tốt, giao nhanh 1–3 ngày, đổi size 7 ngày.') }}">
    <meta name="twitter:image"
        content="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}">
    <meta name="twitter:creator" content="{{ $settings->seo_author ?? 'NOBI FASHION' }}">

    <link rel="canonical"
        href="{{ $settings->site_url ?? 'https://nobifashion.vn' }}/san-pham/{{ $product->slug ?? '' }}">
    <link rel="alternate" hreflang="vi"
        href="{{ $settings->site_url ?? 'https://nobifashion.vn' }}/san-pham/{{ $product->slug ?? '' }}">
    <link rel="alternate" hreflang="x-default"
        href="{{ $settings->site_url ?? 'https://nobifashion.vn' }}/san-pham/{{ $product->slug ?? '' }}">
@endsection

@section('foot')
    <script src="{{ asset('clients/assets/js/single.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('phone-request-form');
            const phoneInput = document.getElementById('phone-input');
            const submitBtn = document.getElementById('phone-submit-btn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            const messageDiv = document.getElementById('phone-request-message');

            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const phone = phoneInput.value.trim();
                    
                    // Validate phone
                    if (!phone || !/^[0-9]{10,11}$/.test(phone)) {
                        showMessage('Vui lòng nhập số điện thoại hợp lệ (10-11 chữ số).', 'error');
                        return;
                    }

                    // Disable form
                    submitBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnLoading.style.display = 'inline';
                    messageDiv.style.display = 'none';

                    try {
                        const formData = new FormData(form);
                        const response = await fetch('{{ route("client.product.phone.request") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]')?.value,
                                'Accept': 'application/json',
                            },
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            showMessage(data.message, 'success');
                            form.reset();
                        } else {
                            showMessage(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showMessage('Không thể kết nối đến server. Vui lòng thử lại sau.', 'error');
                    } finally {
                        // Enable form
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoading.style.display = 'none';
                    }
                });

                function showMessage(message, type) {
                    messageDiv.textContent = message;
                    messageDiv.style.display = 'block';
                    messageDiv.style.backgroundColor = type === 'success' ? '#d1fae5' : '#fee2e2';
                    messageDiv.style.color = type === 'success' ? '#065f46' : '#991b1b';
                    messageDiv.style.border = `1px solid ${type === 'success' ? '#10b981' : '#ef4444'}`;
                    
                    // Auto hide after 5 seconds
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 5000);
                }
            }
        });
    </script>
@endsection

@section('schema')
    @include('clients.templates.schema_product')
@endsection


@section('content')
    <main class="nobifashion_single">
        <!-- Breadcrumb -->
        <section>
            @php
                // Lấy danh mục đầu tiên của sản phẩm
                $categoryBreadcrumb = $product?->primaryCategory?->first();

                // Truy ngược lên cha để tạo breadcrumb path
                $breadcrumbPath = collect();
                while ($categoryBreadcrumb) {
                    $breadcrumbPath->prepend($categoryBreadcrumb); // đưa vào đầu mảng
                    $categoryBreadcrumb = $categoryBreadcrumb->parent;
                }
            @endphp

            <div class="nobifashion_single_breadcrumb">
                <a href="{{ url('/') }}">Trang chủ</a>
                <span class="separator">>></span>

                @if ($breadcrumbPath->isNotEmpty())
                    @foreach ($breadcrumbPath as $breadcrumb)
                        <a href="{{ route('client.product.category.index', $breadcrumb->slug) }}">{{ $breadcrumb->name }}</a>
                        <span class="separator">>></span>
                    @endforeach
                @endif

                <span class="breadcrumb-current">{{ $product->name }}</span>
            </div>
        </section>

        <!-- Thông tin sản phẩm -->
        <section>
            <div class="nobifashion_single_info">
                <div class="nobifashion_single_info_images">
                    <div class="nobifashion_single_info_images_main">
                        <img loading="eager" fetchpriority="high" width="500" height="500" decoding="async"
                            src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                            alt="{{ $product->primaryImage->alt ?? null ?: renderMeta($product->name) ?? 'NOBI FASHION' }}"
                            title="{{ $product->primaryImage->title ?? null ?: renderMeta($product->name) ?? 'NOBI FASHION' }}"
                            class="nobifashion_single_info_images_main_image nobifashion_single_image_clickable"
                            data-default-src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? 'no-image.webp')) }}"
                            style="cursor: pointer;">
                    </div>
                    <div class="nobifashion_single_info_images_gallery">
                        @foreach ($product->images as $image)
                            <img data-src="{{ asset('clients/assets/img/clothes/' . ($image->url ?? 'no-image.webp')) }}"
                                width="80px" height="80px"
                                decoding="async"
                                src="{{ asset('clients/assets/img/clothes/' . ($image->url ?? 'no-image.webp')) }}"
                                alt="{{ $image->alt ?? (renderMeta($product->name) ?? 'NOBI FASHION') }}"
                                title="{{ $image->title ?? (renderMeta($product->name) ?? 'NOBI FASHION') }}"
                                class="nobifashion_single_info_images_gallery_image {{ $image->is_primary ? 'nobifashion_single_info_images_gallery_image_active' : '' }}">
                        @endforeach
                    </div>
                    <div class="nobifashion_single_info_images_support">
                        <form class="nobifashion_single_info_images_support_form" id="phone-request-form" method="POST">
                            @csrf
                            <div class="nobifashion_single_info_images_support_form_group">
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="text" 
                                    placeholder="Nhập số điện thoại để được tư vấn (NOBI FASHION)."
                                    name="phone" 
                                    id="phone-input"
                                    class="nobifashion_single_info_images_support_form_group_input"
                                    required
                                    pattern="[0-9]{10,11}"
                                    maxlength="11">
                                <button type="submit" class="nobifashion_single_info_images_support_form_group_btn" id="phone-submit-btn">
                                    <span class="btn-text">Gửi yêu cầu</span>
                                    <span class="btn-loading" style="display: none;">Đang gửi...</span>
                                </button>
                            </div>
                            <div class="nobifashion_single_info_images_support_form_notice">
                                <p class="nobifashion_single_info_images_support_form_notice_text">Để lại số điện thoại,
                                    NOBI FASHION sẽ tư vấn cho bạn.</p>
                                <div id="phone-request-message" style="display: none; margin-top: 10px; padding: 8px; border-radius: 4px; font-size: 13px;"></div>
                            </div>
                        </form>
                    </div>
                </div>

                @php
                    $item = $product->isInFlashSale() ? $product->currentFlashSaleItem()->first() : $product;

                    $original = $item->original_price ?? ($item->price ?? 0);
                    $sale = $item->sale_price ?? 0;
                    // dd($product->currentFlashSale()->first())
                @endphp

                <div class="nobifashion_single_info_specifications">
                    @if ($product->isInFlashSale())
                        <script>
                            const endTime = new Date("{{ optional($product->currentFlashSale()->first())->end_time }}").getTime();
                        </script>
                        <div class="nobifashion_single_info_specifications_deal">
                            <div class="nobifashion_single_info_specifications_label">
                                ⚡ SĂN DEAL
                            </div>
                            @php
                                $stock = (int) ($item->stock ?? 0);
                                $sold = (int) ($item->sold ?? 0);
                                $percentage = $stock > 0 ? min(100, round(($sold / $stock) * 100)) : 0;
                            @endphp

                            <div class="nobifashion_single_info_specifications_progress">
                                <div class="nobifashion_single_info_specifications_progress_bar"
                                    style="width: {{ $percentage }}%;"></div>
                            </div>
                            <div class="nobifashion_single_info_specifications_time">
                                <span class="nobifashion_single_info_specifications_end_time">Kết thúc trong</span>
                                <div class="nobifashion_single_info_specifications_countdown">
                                    <div
                                        class="nobifashion_single_info_specifications_box nobifashion_single_info_specifications_box_days">
                                        00</div>
                                    <span>:</span>
                                    <div
                                        class="nobifashion_single_info_specifications_box nobifashion_single_info_specifications_box_house">
                                        00</div>
                                    <span>:</span>
                                    <div
                                        class="nobifashion_single_info_specifications_box nobifashion_single_info_specifications_box_minute">
                                        00</div>
                                    <span>:</span>
                                    <div
                                        class="nobifashion_single_info_specifications_box nobifashion_single_info_specifications_box_second">
                                        00</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <h1 class="nobifashion_single_info_specifications_title">
                        {{ renderMeta($product->name) ?? 'Sản phẩm thời trang chính hãng - NOBI FASHION' }}</h1>

                    <div class="nobifashion_single_info_specifications_brand">
                        <!-- Thương hiệu + Mã sản phẩm -->
                        <div class="nobifashion_single_info_specifications_brand_left">
                            <span>Mã tìm kiếm:
                                <strong
                                    class="nobifashion_single_info_specifications_brand_code">{{ $product->sku }}</strong>
                            </span>
                        </div>

                        <!-- Đánh giá -->
                        <div class="nobifashion_single_info_specifications_brand_right">
                            <span class="nobifashion_single_info_specifications_brand_stars">
                                @php
                                    $star = rand(4, 5);
                                    for ($i = 1; $i <= $star; $i++) {
                                        if ($star == 4) {
                                            echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z"/></svg>';

                                            if ($i == 4) {
                                                echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M320.1 417.6C330.1 417.6 340 419.9 349.1 424.6L423.5 462.5L410.5 380C407.3 359.8 414 339.3 428.4 324.8L487.4 265.7L404.9 252.6C384.7 249.4 367.2 236.7 357.9 218.5L319.9 144.1L319.9 417.7zM489.4 553C482.1 558.3 472.4 559.1 464.4 555L320.1 481.6L175.8 555C167.8 559.1 158.1 558.3 150.8 553C143.5 547.7 139.8 538.8 141.2 529.8L166.4 369.9L52 255.4C45.6 249 43.4 239.6 46.2 231C49 222.4 56.3 216.1 65.3 214.7L225.2 189.3L298.8 45.1C302.9 37.1 311.2 32 320.2 32C329.2 32 337.5 37.1 341.6 45.1L415 189.3L574.9 214.7C583.8 216.1 591.2 222.4 594 231C596.8 239.6 594.5 249 588.2 255.4L473.7 369.9L499 529.8C500.4 538.7 496.7 547.7 489.4 553z"/></svg>';
                                                break;
                                            }
                                        }
                                        if ($star == 5) {
                                            echo '<svg xmlns="http://www.w3.org/2000/svg" height="10" width="10" viewBox="0 0 640 640"><!--!Font Awesome Free v7.0.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#FFD43B" d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z"/></svg>';
                                        }
                                    }
                                @endphp
                            </span>
                            <span onclick="tabReview()" class="nobifashion_single_info_specifications_brand_reviews">(<a href="#nobifashion_review">{{ rand(10, 1000) }} đánh giá</a>)</span>
                        </div>
                    </div>

                    {{-- Giá sản phẩm --}}

                    <p class="nobifashion_single_info_specifications_price">
                        @if ($original > 0)
                            @if ($sale > 0 && $sale < $original)
                                {{-- Có giá khuyến mãi hợp lệ --}}
                                <meta content="VND">
                                <span class="nobifashion_single_info_specifications_new_price">
                                    {{ number_format($sale, 0, ',', '.') }}₫
                                </span>

                                <meta content="2025-12-31" />
                                <span class="nobifashion_single_info_specifications_old_price"
                                    style="text-decoration:line-through;">
                                    {{ number_format($original, 0, ',', '.') }}₫
                                </span>

                                {{-- Tính % giảm --}}
                                <span class="nobifashion_single_info_specifications_sale">
                                    -{{ round((($original - $sale) / $original) * 100) }}%
                                </span>
                            @else
                                {{-- Không có sale, chỉ hiển thị giá gốc --}}
                                <meta content="2025-12-31" />
                                <span class="nobifashion_single_info_specifications_new_price">
                                    {{ number_format($original, 0, ',', '.') }}₫
                                </span>
                                <span class="nobifashion_single_info_specifications_sale">
                                    <svg style="width: 35px; height: 35px;" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 640 640">
                                        <path fill="#fff"
                                            d="M434.8 54.1C446.7 62.7 451.1 78.3 445.7 91.9L367.3 288L512 288C525.5 288 537.5 296.4 542.1 309.1C546.7 321.8 542.8 336 532.5 344.6L244.5 584.6C233.2 594 217.1 594.5 205.2 585.9C193.3 577.3 188.9 561.7 194.3 548.1L272.7 352L128 352C114.5 352 102.5 343.6 97.9 330.9C93.3 318.2 97.2 304 107.5 295.4L395.5 55.4C406.8 46 422.9 45.5 434.8 54.1z" />
                                    </svg>
                                </span>
                            @endif
                        @endif
                        <a onclick="tabSizeGuide()" href="#nobifashion_main_tab_size_guide" class="nobifashion_main_size_guide">
                        📏 Hướng dẫn chọn size
                        </a>
                    </p>

                    @php
                        $variants = $product->variants ?? collect();
                        // Lấy tất cả keys trong attributes
                        $attributeKeys = collect($product->variants)
                            ->pluck('attributes')
                            ->map(fn($a) => is_string($a) ? json_decode($a, true) : $a)
                            ->flatMap(fn($a) => array_keys($a))
                            ->unique()
                            ->values();

                        // Gom value theo key
                        $attributesGrouped = [];
                        foreach ($attributeKeys as $key) {
                            $attributesGrouped[$key] = collect($product->variants)
                                ->pluck('attributes')
                                ->map(fn($a) => is_string($a) ? json_decode($a, true) : $a)
                                ->pluck($key)
                                ->unique()
                                ->filter()
                                ->values();
                        }
                        $variantsJson = $product->variants
                            ->map(function ($item) {
                                $attrs = is_string($item->attributes)
                                    ? json_decode($item->attributes, true)
                                    : $item->attributes;
                                
                                // Lấy ảnh từ variant: ưu tiên url, nếu không có thì dùng thumbnail_url
                                $variantImage = optional($item->primaryVariantImage);

                                $imageUrl = $variantImage->url ?? $variantImage->thumbnail_url ?? null;
                                
                                return [
                                    'id' => $item->id,
                                    'stock' => $item->stock_quantity,
                                    'price' => $item->price,
                                    'attrs' => $attrs,
                                    'image_url' => $imageUrl, // Chỉ lưu tên file, không có đường dẫn
                                ];
                            })
                            ->toJson();
                    @endphp

                    <script>
                        const variants = {!! $variantsJson !!};
                    </script>

                    @if ($variants->isNotEmpty())
                        @foreach ($attributesGrouped as $key => $values)
                            <div class="nobifashion_single_info_specifications_{{ strtolower($key) }}">
                                <label>
                                    @if ($key == 'size')
                                        {{ 'Kích thước' }}
                                    @endif @if ($key == 'color')
                                        {{ 'Màu sắc' }}
                                    @endif @if ($key == 'weight')
                                        {{ 'Cân nặng' }}
                                    @endif:
                                    <span id="selected-{{ $key }}">-</span>
                                </label>
                                <div class="{{ strtolower($key) }}-list">
                                    @foreach ($values as $index => $val)
                                        @php
                                            $stock = $variants
                                                ->filter(function ($variant) use ($key, $val) {
                                                    $attrs = is_string($variant->attributes)
                                                        ? json_decode($variant->attributes, true)
                                                        : $variant->attributes;

                                                    return isset($attrs[$key]) && $attrs[$key] === $val;
                                                })
                                                ->sum('stock_quantity');
                                            $isDisabled = $stock <= 0;
                                        @endphp
                                        <button class="{{ strtolower($key) }}-option"
                                            data-attr-key="{{ $key }}" data-attr-value="{{ $val }}"
                                            {{ $isDisabled ? 'disabled' : '' }}>
                                            {{ $val }}
                                        </button>
                                        @php
                                            // gán mặc định attr đầu tiên
                                            if (!isset($selectedAttrs[$key]) && $index === 0) {
                                                $selectedAttrs[$key] = $val;
                                            }
                                        @endphp
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <p>Tồn kho: <span id="product-stock"><span style="color: red;">Vui lòng chọn biến
                                    thể!</span></span></p>
                        <style>
                            /* ========== VARIANTS (dynamic) ========== */
                            [class^="nobifashion_single_info_specifications_"] label {
                                display: block;
                                font-weight: 600;
                                margin: 12px 0 8px;
                                color: #333;
                            }

                            [class^="nobifashion_single_info_specifications_size"],
                            [class^="nobifashion_single_info_specifications_color"],
                            [class^="nobifashion_single_info_specifications_materials"],
                            [class^="nobifashion_single_info_specifications_weight"],
                            [class^="nobifashion_single_info_specifications_types"] {
                                display: flex;
                                gap: 8px;
                                flex-wrap: wrap;
                            }

                            [class^="nobifashion_single_info_specifications_"] .size-list,
                            [class^="nobifashion_single_info_specifications_"] .color-list,
                            [class^="nobifashion_single_info_specifications_"] .materials-list,
                            [class^="nobifashion_single_info_specifications_"] .weight-list,
                            [class^="nobifashion_single_info_specifications_"] .types-list {
                                display: flex;
                                gap: 8px;
                                flex-wrap: wrap;
                                margin-left: 20px;
                            }

                            [class^="nobifashion_single_info_specifications_"] .size-option,
                            [class^="nobifashion_single_info_specifications_"] .color-option,
                            [class^="nobifashion_single_info_specifications_"] .material-option,
                            [class^="nobifashion_single_info_specifications_"] .weight-option,
                            [class^="nobifashion_single_info_specifications_"] .type-option {
                                padding: 8px 14px;
                                border: 1px solid #ccc;
                                border-radius: 6px;
                                background: #fff;
                                cursor: pointer;
                                font-size: 14px;
                                transition: all 0.25s ease;
                            }

                            [class^="nobifashion_single_info_specifications_"] .size-option:hover,
                            [class^="nobifashion_single_info_specifications_"] .color-option:hover,
                            [class^="nobifashion_single_info_specifications_"] .material-option:hover,
                            [class^="nobifashion_single_info_specifications_"] .weight-option:hover,
                            [class^="nobifashion_single_info_specifications_"] .type-option:hover {
                                border-color: var(--primary-color);
                                color: var(--primary-color);
                            }

                            [class^="nobifashion_single_info_specifications_"] .size-option.active,
                            [class^="nobifashion_single_info_specifications_"] .color-option.active,
                            [class^="nobifashion_single_info_specifications_"] .material-option.active,
                            [class^="nobifashion_single_info_specifications_"] .weight-option.active,
                            [class^="nobifashion_single_info_specifications_"] .type-option.active {
                                background: var(--primary-color);
                                border-color: var(--primary-color);
                                color: #fff;
                            }
                        </style>
                    @else
                        <p class="nobifashion_single_info_specifications_no_variants">
                            Sản phẩm này không có lựa chọn biến thể (<span style="color: red !important;">Tạm hết!</span>).
                        </p>
                    @endif

                    <!-- Product Actions Form -->
                    <form class="nobifashion_single_info_specifications_actions" action="{{ route('client.cart.add') }}"
                        method="POST">
                        @csrf
                        <!-- Quantity Box -->
                        <div class="nobifashion_single_info_specifications_actions_qty">
                            <button type="button" class="nobifashion_single_info_specifications_actions_btn"
                                onclick="decreaseQty()">−</button>
                            <span class="nobifashion_single_info_specifications_actions_value">1</span>
                            <button type="button" class="nobifashion_single_info_specifications_actions_btn"
                                onclick="increaseQty()">+</button>
                        </div>

                        <!-- Add to Cart -->
                        <button disabled type="submit" name="action" value="add_to_cart"
                            class="nobifashion_single_info_specifications_actions_cart disabled">
                            THÊM VÀO GIỎ
                        </button>

                        <!-- Buy Now (same behavior as Add to Cart) -->
                        <button disabled type="submit" name="action" value="add_to_cart"
                            class="nobifashion_single_info_specifications_actions_buy disabled">
                            MUA NGAY
                        </button>
                        
                        <!-- Favorite button -->
                        <button type="button" data-product-id="{{ $product->id }}" class="nobifashion_fav_btn {{ in_array($product->id, $favoriteProductIds ?? []) ? 'active' : '' }}" aria-label="Yêu thích" style="">
                            @if(in_array($product->id, $favoriteProductIds ?? []))
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="#ff0000" d="M305 151.1L320 171.8L335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1L576 231.7C576 343.9 436.1 474.2 363.1 529.9C350.7 539.3 335.5 544 320 544C304.5 544 289.2 539.4 276.9 529.9C203.9 474.2 64 343.9 64 231.7L64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1z"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="#ff0000" d="M442.9 144C415.6 144 389.9 157.1 373.9 179.2L339.5 226.8C335 233 327.8 236.7 320.1 236.7C312.4 236.7 305.2 233 300.7 226.8L266.3 179.2C250.3 157.1 224.6 144 197.3 144C150.3 144 112.2 182.1 112.2 229.1C112.2 279 144.2 327.5 180.3 371.4C221.4 421.4 271.7 465.4 306.2 491.7C309.4 494.1 314.1 495.9 320.2 495.9C326.3 495.9 331 494.1 334.2 491.7C368.7 465.4 419 421.3 460.1 371.4C496.3 327.5 528.2 279 528.2 229.1C528.2 182.1 490.1 144 443.1 144zM335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1C576 297.7 533.1 358 496.9 401.9C452.8 455.5 399.6 502 363.1 529.8C350.8 539.2 335.6 543.9 320 543.9C304.4 543.9 289.2 539.2 276.9 529.8C240.4 502 187.2 455.5 143.1 402C106.9 358.1 64 297.7 64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1L320 171.8L335 151.1z"/></svg>
                            @endif
                        </button>
                    </form>

                    <div class="nobifashion_single_info_specifications_desc" data-nospinet>
                        <h3 class="nobifashion_single_info_specifications_desc_title">
                            🎁 Khuyến mãi hấp dẫn tại NOBI FASHION
                        </h3>
                        <ul class="nobifashion_single_info_specifications_desc_list">
                            <li class="nobifashion_single_info_specifications_desc_item">
                                <span class="nobifashion_single_info_specifications_desc_number">1</span>
                                Tặng <strong>Bảo hành VIP 3 tháng</strong> cho mọi sản phẩm thời trang từ 500,000đ.
                                <a href="#">Xem chi tiết</a>
                            </li>
                            <li class="nobifashion_single_info_specifications_desc_item">
                                <span class="nobifashion_single_info_specifications_desc_number">2</span>
                                Nhận <strong>voucher 200,000đ</strong> khi mua trọn bộ Outfit (áo + quần + phụ kiện).
                                <a href="#">Xem chi tiết</a>
                            </li>
                            <li class="nobifashion_single_info_specifications_desc_item">
                                <span class="nobifashion_single_info_specifications_desc_number">3</span>
                                Giảm <strong>10%</strong> cho sinh viên khi xuất trình thẻ SV tại quầy thanh toán.
                                <a href="#">Xem chi tiết</a>
                            </li>
                            <li class="nobifashion_single_info_specifications_desc_item">
                                <span class="nobifashion_single_info_specifications_desc_number">4</span>
                                <strong>Miễn phí vận chuyển</strong> cho đơn hàng từ 700,000đ trở lên.
                                <a href="#">Xem chi tiết</a>
                            </li>
                        </ul>

                        {{-- @if ($product->isInFlashSale())
                            <div class="nobifashion_single_info_specifications_desc_flashsale">
                                <strong>⚡ Flash Sale: {{ $product->currentFlashSale()->first()->title }}</strong><br>
                                Diễn ra từ
                                <span class="time">
                                    {{ \Carbon\Carbon::parse($product->currentFlashSale()->first()->start_time)->format('H:i') }}
                                    –
                                    {{ \Carbon\Carbon::parse($product->currentFlashSale()->first()->end_time)->format('H:i') }}
                                </span>
                                ngày
                                <span class="date">
                                    {{ \Carbon\Carbon::parse($product->currentFlashSale()->first()->start_time)->format('d/m') }}
                                </span>.
                                <br>
                                👕 Số lượng có hạn, ưu tiên thanh toán online.<br>
                                ⚠️ Mỗi khách hàng chỉ mua tối đa 1 sản phẩm cùng loại.<br>
                                🕒 Đơn hàng giữ trong 24h, không áp dụng kèm chương trình khuyến mãi khác.
                            </div>
                        @endif --}}
                    </div>


                </div>

                <div class="nobifashion_single_info_policy">
                    <h3 class="nobifashion_single_info_policy_title">CHÍNH SÁCH BÁN HÀNG</h3>
                    <p class="nobifashion_single_info_policy_subtitle">Áp dụng cho từng ngành hàng</p>

                    <!-- MIỄN PHÍ VẬN CHUYỂN -->
                    <div class="nobifashion_single_info_policy_item">
                        <div class="nobifashion_single_info_policy_icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="#444"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M20 8h-3V4H3v13h2a3 3 0 1 0 6 0h4a3 3 0 1 0 6 0h1v-5l-4-4zM5 15V6h10v9H5zm13 1a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-10 1a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm10-4V9.4l2.6 2.6H18z" />
                            </svg>
                        </div>
                        <div class="nobifashion_single_info_policy_content">
                            <strong>MIỄN PHÍ VẬN CHUYỂN</strong>
                        </div>
                    </div>

                    <!-- ĐỔI TRẢ MIỄN PHÍ -->
                    <div class="nobifashion_single_info_policy_item">
                        <div class="nobifashion_single_info_policy_icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="#444"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6a6 6 0 1 1-12 0H4a8 8 0 1 0 8-8z" />
                            </svg>
                        </div>
                        <div class="nobifashion_single_info_policy_content">
                            <strong>ĐỔI TRẢ MIỄN PHÍ</strong>
                        </div>
                    </div>

                    <!-- THANH TOÁN -->
                    <div class="nobifashion_single_info_policy_item">
                        <div class="nobifashion_single_info_policy_icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="#444"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M20 4H4c-1.1 0-2 .9-2 2v3h20V6c0-1.1-.9-2-2-2zm0 5H2v9c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9zm-6 6H6v-2h8v2z" />
                            </svg>
                        </div>
                        <div class="nobifashion_single_info_policy_content">
                            <strong>THANH TOÁN</strong>
                        </div>
                    </div>

                    <!-- HỖ TRỢ MUA NHANH -->
                    <div class="nobifashion_single_info_policy_item">
                        <div class="nobifashion_single_info_policy_icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="#444"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M6.62 10.79a15.055 15.055 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.36 11.36 0 0 0 3.58.57 1 1 0 0 1 1 1v3.5a1 1 0 0 1-1 1C9.27 21 3 14.73 3 7.5a1 1 0 0 1 1-1H7.5a1 1 0 0 1 1 1c0 1.25.2 2.47.57 3.58a1 1 0 0 1-.24 1.01l-2.2 2.2z" />
                            </svg>
                        </div>
                        <div class="nobifashion_single_info_policy_content">
                            <strong>HỖ TRỢ MUA NHANH</strong>
                            <p><span class="nobifashion_single_info_policy_hotline">Call:
                                    {{ preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1.$2.$3', $settings->contact_phone ?? '0382941465') }}
                                    - Zalo:
                                    {{ preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1.$2.$3', $settings->contact_zalo ?? '0382941465') }}</span><br>từ
                                8:30 - 22:30 mỗi ngày.</p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
                        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
                        <span style="padding: 0 12px; color: #f74a4a; font-weight: bold;">Khuyễn mãi & Ưu đãi</span>
                        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
                    </div>

                    <div class="nobifashion_single_info_voucher"
                        style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.8; width: fit-content; max-width: 100%; margin: auto; text-align: start;">
                        @foreach ($vouchers as $voucher)
                            @php
                                $type = $voucher->type ?? '';
                                $code = $voucher->code ?? '';
                                $value = $voucher->value ?? '';
                                $min = $voucher->min_order_amount ?? '';
                                $max = $voucher->max_discount_amount ?? '';
                            @endphp

                            @if ($type === 'free_ship')
                                <p style="margin:4px 0;font-size:14px;">
                                    🎫 Nhập mã <strong>{{ $code }}</strong> MIỄN PHÍ SHIP
                                    @if ($value)
                                        TỐI ĐA <span style="color:red">{{ number_format($value, 0, ',', '.') }}đ</span>
                                    @endif
                                    @if ($min)
                                        CHO ĐƠN TỪ <span style="color:red">{{ number_format($min, 0, ',', '.') }}đ</span>
                                    @endif
                                </p>
                            @elseif ($type === 'percentage')
                                <p style="margin:4px 0;font-size:14px;">
                                    🎫 Nhập mã <strong>{{ $code }}</strong> GIẢM <span
                                        style="color:red">{{ number_format($value, 0, ',', '.') }}%</span>
                                    @if ($max)
                                        TỐI ĐA <span style="color:red">{{ number_format($max, 0, ',', '.') }}đ</span>
                                    @endif
                                    @if ($min)
                                        CHO ĐƠN TỪ <span style="color:red">{{ number_format($min, 0, ',', '.') }}đ</span>
                                    @endif
                                </p>
                            @elseif ($type === 'fixed_amount')
                                <p style="margin:4px 0;font-size:14px;">
                                    🎫 Nhập mã <strong>{{ $code }}</strong> GIẢM <span
                                        style="color:red">{{ number_format($value, 0, ',', '.') }}</span>
                                    @if ($min)
                                        CHO ĐƠN TỪ <span style="color:red">{{ number_format($min, 0, ',', '.') }}đ</span>
                                    @endif
                                </p>
                            @endif
                        @endforeach

                        <p style="margin: 4px 0; font-size: 14px;"><span>🚚</span> <strong
                                style="font-size: 14px;">FREESHIP 100%</strong> đơn từ 1000K</p>

                        <div class="nobifashion_single_info_voucher_code" style="margin-top: 16px;">
                            <p style="margin-bottom: 8px;">Mã giảm giá bạn có thể sử dụng:</p>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                                @foreach ($vouchers as $voucher)
                                    <div class="nobifashion_single_info_voucher_code_item"
                                        style="background: #000; color: #00ffff; padding: 6px 12px; border-radius: 8px; font-weight: bold; font-size: 13px; font-family: monospace; clip-path: polygon(10% 0%, 90% 0%, 90% 35%, 100% 50%, 90% 65%, 90% 100%, 10% 100%, 10% 65%, 0% 50%, 10% 35%); cursor: pointer;">
                                        {{ $voucher->code ?? 'NOBI2025' }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <!-- Popup overlay -->
                    @if($vouchers->isNotEmpty())
                        <div id="voucherPopup" class="nobifashion_main_show_popup_voucher_overlay">
                            <div class="nobifashion_main_show_popup_voucher_box">
                                <button class="nobifashion_main_show_popup_voucher_close">&times;</button>
                                <h2>🎉 Chúc mừng bạn!</h2>
                                <img width="100" src="{{ asset('clients/assets/img/other/party.gif') }}"
                                    alt="Voucher NOBI FASHION">
                                <p>Bạn đã nhận được voucher đặc biệt từ shop:</p>
                                @foreach ($vouchers as $voucher)
                                    <div class="nobifashion_main_show_popup_voucher_code">{{ $voucher->code }}</div>
                                @endforeach
                                <p>Dùng ngay để được ưu đãi hấp dẫn 💖</p>
                            </div>
                        </div>
                    @else
                        <div id="voucherPopup" class="nobifashion_main_show_popup_voucher_overlay">
                            <div class="nobifashion_main_show_popup_voucher_box">
                                <button class="nobifashion_main_show_popup_voucher_close">&times;</button>
                                {{-- <h2>🎉 Chúc mừng bạn!</h2> --}}
                            </div>
                        </div>
                    @endif

                </div>
            </div>
            <div id="nobifashion_main_tab_size_guide" style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
                <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
                <span style="padding: 0 12px; color: #f74a4a; font-weight: bold;">Mô tả sản phẩm</span>
                <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
            </div>
        </section>

        <!-- Mô tả sản phẩm -->
        <section id="nobifashion_review">
            <div class="nobifashion_single_desc">
                <div class="nobifashion_single_desc_button">
                    <button class="nobifashion_single_desc_button_describe .nobifashion_single_desc_button_active">Mô
                        tả</button>
                    <button class="nobifashion_single_desc_button_add_info">Hướng dẫn chọn Size</button>
                    <button class="nobifashion_single_desc_button_reviews">Đánh giá</button>
                </div>
                <div class="nobifashion_single_desc_tabs">
                    <div class="nobifashion_single_desc_tabs_describe .nobifashion_single_desc_tabs_active">
                        <div class="nobifashion_single_desc_tabs_describes">
                            <div class="nobifashion_single_desc_tabs_describe_specifications">

                                {!! $product->description ?? '<p>Chưa có mô tả cho sản phẩm này.</p>' !!}

                                <div class="nobifashion_single_info_images_tags">
                                    <h6 class="nobifashion_single_info_images_tags_title">Thẻ: </h6>
                                    @foreach ($product->tags as $tag)
                                        <a href="#"><span
                                                class="nobifashion_single_info_images_tags_tag">#{{ $tag->name ?? 'thoi-trang' }}</span></a>
                                    @endforeach
                                </div>

                            </div>
                            <aside class="nobifashion_single_sidebar">
                                <div class="sticky-box">
                                    @include('clients.templates.product_new')
                                </div>
                            </aside>
                        </div>
                    </div>

                    <div class="nobifashion_single_desc_tabs_add_info">
                        @include('clients.templates.size')
                    </div>
                    <div class="nobifashion_single_desc_tabs_reviews">
                        @if ($product?->productReviews?->isNotEmpty() && $product?->productVariants?->isNotEmpty())
                            <div class="nobifashion_single_desc_tabs_reviews_star">
                                <div class="nobifashion_single_desc_tabs_reviews_star">
                                    <p class="nobifashion_single_desc_tabs_reviews_star_title">4.8 / 5</p>
                                    <p class="nobifashion_single_desc_tabs_reviews_stars">⭐⭐⭐⭐⭐</p>
                                </div>
                                <div class="nobifashion_single_desc_tabs_reviews_toolbar">
                                    <div
                                        class="nobifashion_single_desc_tabs_reviews_toolbar_all .nobifashion_single_desc_tabs_reviews_toolbar_active">
                                        Tất cả</div>
                                    <div class="nobifashion_single_desc_tabs_reviews_toolbar_5_star">5 ⭐</div>
                                    <div class="nobifashion_single_desc_tabs_reviews_toolbar_4_star">4 ⭐</div>
                                    <div class="nobifashion_single_desc_tabs_reviews_toolbar_3_star">3 ⭐</div>
                                    <div class="nobifashion_single_desc_tabs_reviews_toolbar_2_star">2 ⭐</div>
                                    <div class="nobifashion_single_desc_tabs_reviews_toolbar_1_star">1 ⭐</div>
                                </div>
                            </div>
                            <div class="nobifashion_single_desc_tabs_reviews_comments">
                                @foreach ($product->productReviews as $review)
                                    <div class="nobifashion_single_desc_tabs_reviews_comment">
                                        <div class="nobifashion_single_desc_tabs_reviews_comment_avatar">
                                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/12/User_icon_2.svg/2048px-User_icon_2.svg.png"
                                                alt="">
                                        </div>
                                        <div class="nobifashion_single_desc_tabs_reviews_comment_content">
                                            <h5 class="nobifashion_single_desc_tabs_reviews_comment_content_name">
                                                {{ $review->account->name ?? 'Khách hàng' }}</h5>
                                            <p class="nobifashion_single_desc_tabs_reviews_comment_content_star">
                                                @for ($x = 1; $x <= ($review->rating ?? 5); $x++)
                                                    ⭐
                                                @endfor
                                            </p>
                                            <p class="nobifashion_single_desc_tabs_reviews_comment_content_time">
                                                {{ \Carbon\Carbon::parse($review->created ?? now())->format('d/m/y') }}
                                            </p>
                                            <p class="nobifashion_single_desc_tabs_reviews_comment_content_material">
                                                <span
                                                    class="nobifashion_single_desc_tabs_reviews_comment_content_material_title">Chất
                                                    liệu: </span> <span
                                                    class="nobifashion_single_desc_tabs_reviews_comment_content_material_desc">{{ optional($product->productVariants->first())->material ?? 'Vải thoáng mát' }}</span>
                                            </p>
                                            <p class="nobifashion_single_desc_tabs_reviews_comment_content_desc">
                                                {{ $review->comment ?? 'Sản phẩm đẹp, chất liệu tốt, giao nhanh.' }}
                                            </p>
                                            <div class="nobifashion_single_desc_tabs_reviews_comment_content_gallery">
                                                @foreach ($review->gallery ?? [] as $img)
                                                    <img width="50px"
                                                        src="{{ asset('clients/assets/img/clothes/' . ($img ?? 'no-image.webp')) }}"
                                                        alt="{{ $review->comment ?? 'Ảnh đánh giá' }}"
                                                        title="Đánh giá của {{ $review->account->name ?? 'Khách hàng' }}">
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="nobifashion_single_desc_tabs_reviews_pagination">
                                <button class="active">1</button>
                                <button>2</button>
                                <button>3</button>
                                <button>4</button>
                                <button>»</button>
                            </div>
                        @else
                            Không có review hoặc biến thể
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQS --}}
        @include('clients.templates.faqs')

        {{-- Sản phẩm liên quan --}}
        @include('clients.templates.product_related')

        <section>
            <div class="nobifashion_chat">
                <!-- Nút cuộn lên đầu trang -->
                <div class="nobifashion_back_to_top">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path
                            d="M270.7 9.7C268.2 3.8 262.4 0 256 0s-12.2 3.8-14.7 9.7L197.2 112.6c-3.4 8-5.2 16.5-5.2 25.2l0 77-144 84L48 280c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 56 0 32 0 24c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8 144 0 0 32.7L133.5 468c-3.5 3-5.5 7.4-5.5 12l0 16c0 8.8 7.2 16 16 16l96 0 0-64c0-8.8 7.2-16 16-16s16 7.2 16 16l0 64 96 0c8.8 0 16-7.2 16-16l0-16c0-4.6-2-9-5.5-12L320 416.7l0-32.7 144 0 0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-24 0-32 0-56c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 18.8-144-84 0-77c0-8.7-1.8-17.2-5.2-25.2L270.7 9.7z" />
                    </svg>
                </div>

                <!-- Zalo -->
                <a href="https://zalo.me/{{ $settings->contact_zalo ?? '0382941465' }}" target="_blank"
                    class="nobifashion_chat_zalo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path
                            d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z" />
                    </svg>
                </a>

                <!-- Gọi điện -->
                <a href="tel:{{ $settings->contact_phone ?? '0382941465' }}" class="nobifashion_chat_phone">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path
                            d="M256.6 8C116.5 8 8 110.3 8 248.6c0 72.3 29.7 134.8 78.1 177.9 8.4 7.5 6.6 11.9 8.1 58.2A19.9 19.9 0 0 0 122 502.3c52.9-23.3 53.6-25.1 62.6-22.7C337.9 521.8 504 423.7 504 248.6 504 110.3 396.6 8 256.6 8zm149.2 185.1l-73 115.6a37.4 37.4 0 0 1 -53.9 9.9l-58.1-43.5a15 15 0 0 0 -18 0l-78.4 59.4c-10.5 7.9-24.2-4.6-17.1-15.7l73-115.6a37.4 37.4 0 0 1 53.9-9.9l58.1 43.5a15 15 0 0 0 18 0l78.4-59.4c10.4-8 24.1 4.5 17.1 15.6z" />
                    </svg>
                </a>

                <!-- Facebook -->
                <a href="{{ $settings->facebook_link ?? 'https://www.facebook.com/nobifashion.vn' }}" target="_blank"
                    class="nobifashion_chat_facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                        <path
                            d="M320 0c17.7 0 32 14.3 32 32l0 64 120 0c39.8 0 72 32.2 72 72l0 272c0 39.8-32.2 72-72 72l-304 0c-39.8 0-72-32.2-72-72l0-272c0-39.8 32.2-72 72-72l120 0 0-64c0-17.7 14.3-32 32-32zM208 384c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zM264 256a40 40 0 1 0 -80 0 40 40 0 1 0 80 0zm152 40a40 40 0 1 0 0-80 40 40 0 1 0 0 80zM48 224l16 0 0 192-16 0c-26.5 0-48-21.5-48-48l0-96c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48l0 96c0 26.5-21.5 48-48 48l-16 0 0-192 16 0z" />
                    </svg>
                </a>
            </div>
        </section>
    </main>

    <div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
        <span style="padding: 0 12px; color: #f74a4a; font-weight: bold; text-align: center;">Đăng ký Email nhận thông báo từ {{ $settings->subname ?? '' }}</span>
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
    </div>

    @include('clients.templates.call')

    <div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
        <span style="padding: 0 12px; color: #f74a4a; font-weight: bold; text-align: center;">Đăng ký Email nhận thông báo từ {{ $settings->subname ?? '' }}</span>
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
    </div>

    <!-- Image Lightbox Modal -->
    <div id="nobifashion_image_lightbox" class="nobifashion_lightbox">
        <div class="nobifashion_lightbox_overlay"></div>
        <div class="nobifashion_lightbox_container">
            <button class="nobifashion_lightbox_close" aria-label="Đóng">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="24" height="24" fill="currentColor">
                    <path d="M324.5 411.1c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6L214.6 256 347.1 123.5c6.2-6.2 6.2-16.4 0-22.6s-16.4-6.2-22.6 0L192 233.4 59.5 100.9c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6L169.4 256 36.9 388.5c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L192 278.6 324.5 411.1z"/>
                </svg>
            </button>
            
            <button class="nobifashion_lightbox_prev" aria-label="Ảnh trước">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="24" height="24" fill="currentColor">
                    <path d="M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256 246.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"/>
                </svg>
            </button>
            
            <button class="nobifashion_lightbox_next" aria-label="Ảnh sau">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="24" height="24" fill="currentColor">
                    <path d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"/>
                </svg>
            </button>

            <div class="nobifashion_lightbox_content">
                <div class="nobifashion_lightbox_image_wrapper">
                    <img id="nobifashion_lightbox_image" src="" alt="" class="nobifashion_lightbox_image">
                </div>
                
                <div class="nobifashion_lightbox_controls">
                    <button class="nobifashion_lightbox_zoom_in" aria-label="Phóng to">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20" fill="currentColor">
                            <path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288zm64-208c0-8.8-7.2-16-16-16s-16 7.2-16 16v48H192c-8.8 0-16 7.2-16 16s7.2 16 16 16h48v48c0 8.8 7.2 16 16 16s16-7.2 16-16V288h48c8.8 0 16-7.2 16-16s-7.2-16-16-16H272V144z"/>
                        </svg>
                    </button>
                    <button class="nobifashion_lightbox_zoom_out" aria-label="Thu nhỏ">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20" fill="currentColor">
                            <path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM144 192c-8.8 0-16 7.2-16 16s7.2 16 16 16H272c8.8 0 16-7.2 16-16s-7.2-16-16-16H144z"/>
                        </svg>
                    </button>
                    <button class="nobifashion_lightbox_reset" aria-label="Đặt lại">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20" fill="currentColor">
                            <path d="M463.5 224H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1c-87.5 87.5-87.5 229.3 0 316.8s229.3 87.5 316.8 0c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0c-62.5 62.5-163.8 62.5-226.3 0s-62.5-163.8 0-226.3c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5z"/>
                        </svg>
                    </button>
                    <a id="nobifashion_lightbox_download" class="nobifashion_lightbox_download" download aria-label="Tải xuống">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20" fill="currentColor">
                            <path d="M288 32c0-17.7-14.3-32-32-32s-32 14.3-32 32V274.7l-73.4-73.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l128 128c12.5 12.5 32.8 12.5 45.3 0l128-128c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L288 274.7V32zM64 352c-35.3 0-64 28.7-64 64v32c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V416c0-35.3-28.7-64-64-64H346.5l-45.3 45.3c-25 25-65.5 25-90.5 0L165.5 352H64zm368 56a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="nobifashion_lightbox_thumbnails">
                @foreach ($product->images as $index => $image)
                    <img src="{{ asset('clients/assets/img/clothes/' . ($image->url ?? 'no-image.webp')) }}"
                        alt="{{ $image->alt ?? (renderMeta($product->name) ?? 'NOBI FASHION') }}"
                        class="nobifashion_lightbox_thumbnail {{ $image->is_primary ? 'active' : '' }}"
                        data-index="{{ $index }}"
                        data-src="{{ asset('clients/assets/img/clothes/' . ($image->url ?? 'no-image.webp')) }}">
                @endforeach
            </div>
        </div>
    </div>
@endsection
