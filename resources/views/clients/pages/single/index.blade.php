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
    <script src="{{ asset('clients/assets/js/single.js?v=' . filemtime(public_path('clients/assets/js/single.js'))) }}"></script>
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
            @php
                $currentFlashSale = $product->isInFlashSale() ? $product->currentFlashSale()->first() : null;
                $item = $product->isInFlashSale() ? $product->currentFlashSaleItem()->first() : $product;
                $original = (float) ($item->original_price ?? ($item->price ?? 0));
                $sale = (float) ($item->sale_price ?? 0);
                $displayCurrentPrice = $original > 0 && $sale > 0 && $sale < $original ? $sale : $original;
                $displayOriginalPrice = $sale > 0 && $sale < $original ? $original : null;
                $discountPercent = $displayOriginalPrice
                    ? (int) round((($displayOriginalPrice - $displayCurrentPrice) / $displayOriginalPrice) * 100)
                    : null;
                $savedAmount = $displayOriginalPrice ? max(0, $displayOriginalPrice - $displayCurrentPrice) : 0;
                $galleryImages = $product->images->isNotEmpty() ? $product->images : collect([$product->primaryImage])->filter();
                $voucherItems = collect($vouchers ?? []);
                $variants = $product->variants ?? collect();
                $attributeLabels = [
                    'size' => 'Kích thước',
                    'color' => 'Màu sắc',
                    'weight' => 'Cân nặng',
                    'material' => 'Chất liệu',
                    'materials' => 'Chất liệu',
                    'type' => 'Kiểu dáng',
                    'types' => 'Kiểu dáng',
                ];
                $colorHexMap = [
                    'trang' => '#f5f5f4',
                    'white' => '#f5f5f4',
                    'den' => '#111827',
                    'black' => '#111827',
                    'xam' => '#9ca3af',
                    'grey' => '#9ca3af',
                    'gray' => '#9ca3af',
                    'xanh' => '#2563eb',
                    'blue' => '#2563eb',
                    'navy' => '#1e3a8a',
                    'do' => '#dc2626',
                    'red' => '#dc2626',
                    'hong' => '#ec4899',
                    'pink' => '#ec4899',
                    'vang' => '#f59e0b',
                    'yellow' => '#f59e0b',
                    'be' => '#d6b58a',
                    'kem' => '#f3e8d0',
                    'nau' => '#8b5e3c',
                    'brown' => '#8b5e3c',
                    'xanh la' => '#15803d',
                    'green' => '#15803d',
                    'olive' => '#556b2f',
                    'cam' => '#f97316',
                    'orange' => '#f97316',
                    'tim' => '#7c3aed',
                    'purple' => '#7c3aed',
                ];
                $resolveSwatchColor = function (?string $value) use ($colorHexMap) {
                    $normalized = mb_strtolower(\Illuminate\Support\Str::ascii(trim((string) $value)));
                    foreach ($colorHexMap as $keyword => $hex) {
                        if (str_contains($normalized, $keyword)) {
                            return $hex;
                        }
                    }

                    return 'linear-gradient(135deg, #e5e7eb 0%, #9ca3af 100%)';
                };
                $attributeKeys = collect($product->variants)
                    ->pluck('attributes')
                    ->map(fn($attr) => is_string($attr) ? json_decode($attr, true) : $attr)
                    ->flatMap(fn($attr) => array_keys($attr ?? []))
                    ->unique()
                    ->values();
                $attributesGrouped = [];
                foreach ($attributeKeys as $key) {
                    $attributesGrouped[$key] = collect($product->variants)
                        ->pluck('attributes')
                        ->map(fn($attr) => is_string($attr) ? json_decode($attr, true) : $attr)
                        ->pluck($key)
                        ->unique()
                        ->filter()
                        ->values();
                }
                $variantsJson = $product->variants
                    ->map(function ($variantItem) {
                        $attrs = is_string($variantItem->attributes)
                            ? json_decode($variantItem->attributes, true)
                            : $variantItem->attributes;
                        $variantImage = optional($variantItem->primaryVariantImage);

                        return [
                            'id' => $variantItem->id,
                            'stock' => (int) $variantItem->stock_quantity,
                            'price' => (float) ($variantItem->price ?? 0),
                            'attrs' => $attrs,
                            'image_url' => $variantImage->url ?? $variantImage->thumbnail_url ?? null,
                        ];
                    })
                    ->toJson();
                $bestVoucherPrice = null;
                foreach ($voucherItems as $voucher) {
                    $minOrder = (float) ($voucher->min_order_amount ?? 0);
                    if ($minOrder > 0 && $displayCurrentPrice < $minOrder) {
                        continue;
                    }

                    $discount = 0;
                    if (($voucher->type ?? '') === 'percentage') {
                        $discount = $displayCurrentPrice * ((float) ($voucher->value ?? 0) / 100);
                        $maxDiscount = (float) ($voucher->max_discount_amount ?? 0);
                        if ($maxDiscount > 0) {
                            $discount = min($discount, $maxDiscount);
                        }
                    } elseif (($voucher->type ?? '') === 'fixed_amount') {
                        $discount = (float) ($voucher->value ?? 0);
                    }

                    if ($discount > 0) {
                        $candidate = max(0, $displayCurrentPrice - $discount);
                        $bestVoucherPrice = $bestVoucherPrice === null ? $candidate : min($bestVoucherPrice, $candidate);
                    }
                }
                $voucherCards = $voucherItems->take(5)->map(function ($voucher) {
                    $type = $voucher->type ?? '';
                    $value = (float) ($voucher->value ?? 0);

                    return [
                        'code' => $voucher->code ?? '',
                        'icon' => $type === 'free_ship' ? '🚚' : '%',
                        'accent' => $type === 'free_ship' ? '#1a73e8' : '#e5252a',
                        'label' => match ($type) {
                            'free_ship' => 'FreeShip',
                            'percentage' => 'Giảm ' . number_format($value, 0, ',', '.') . '%',
                            'fixed_amount' => 'Giảm ' . number_format($value, 0, ',', '.') . 'đ',
                            default => $voucher->code ?? 'Ưu đãi',
                        },
                    ];
                });
                $defaultStockValue = $variants->isNotEmpty()
                    ? (int) ($variants->max('stock_quantity') ?? 0)
                    : (int) ($product->stock_quantity ?? 0);
                $defaultStockBase = max(1, $defaultStockValue);
                $defaultStockPercent = min(100, max(8, (int) round(($defaultStockValue / $defaultStockBase) * 100)));
                $defaultStockNote = $variants->isNotEmpty()
                    ? 'Chọn đủ thuộc tính để xem tồn kho chính xác.'
                    : ($defaultStockValue > 0 ? 'Sản phẩm đang sẵn hàng, có thể đặt mua ngay.' : 'Sản phẩm đang tạm hết hàng.');
            @endphp

            <script>
                const variants = {!! $variantsJson !!};
            </script>

            @if ($product->isInFlashSale() && $currentFlashSale)
                @php
                    $flashSaleStock = max(1, (int) ($item->stock ?? 0));
                    $flashSaleSold = max(0, (int) ($item->sold ?? 0));
                    $flashSalePercent = min(100, (int) round(($flashSaleSold / $flashSaleStock) * 100));
                @endphp
                <script>
                    const endTime = new Date("{{ $currentFlashSale->end_time }}").getTime();
                </script>
            @endif

            <div class="nobifashion_single_info">
                <div class="nobifashion_single_info_wrapper">
                    <div class="nobifashion_single_info_gallery">
                        <div class="nobifashion_single_info_gallery_stage">
                            <div class="nobifashion_single_info_gallery_thumbs">
                                @forelse ($galleryImages as $image)
                                    <img
                                        data-src="{{ asset('clients/assets/img/clothes/' . ($image->url ?? 'no-image.webp')) }}"
                                        src="{{ asset('clients/assets/img/clothes/' . ($image->url ?? 'no-image.webp')) }}"
                                        alt="{{ $image->alt ?? (renderMeta($product->name) ?? 'NOBI FASHION') }}"
                                        title="{{ $image->title ?? (renderMeta($product->name) ?? 'NOBI FASHION') }}"
                                        width="64"
                                        height="64"
                                        decoding="async"
                                        class="nobifashion_single_info_gallery_thumb nobifashion_single_info_images_gallery_image {{ $image->is_primary || $loop->first ? 'nobifashion_single_info_images_gallery_image_active' : '' }}">
                                @empty
                                    <img
                                        data-src="{{ asset('clients/assets/img/clothes/no-image.webp') }}"
                                        src="{{ asset('clients/assets/img/clothes/no-image.webp') }}"
                                        alt="{{ renderMeta($product->name) ?? 'NOBI FASHION' }}"
                                        title="{{ renderMeta($product->name) ?? 'NOBI FASHION' }}"
                                        width="64"
                                        height="64"
                                        decoding="async"
                                        class="nobifashion_single_info_gallery_thumb nobifashion_single_info_images_gallery_image nobifashion_single_info_images_gallery_image_active">
                                @endforelse
                            </div>

                            <div class="nobifashion_single_info_gallery_main nobifashion_single_info_images_main">
                                <img
                                    loading="eager"
                                    fetchpriority="high"
                                    width="500"
                                    height="500"
                                    decoding="async"
                                    src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? optional($galleryImages->first())->url ?? 'no-image.webp')) }}"
                                    alt="{{ $product?->primaryImage?->alt ?? renderMeta($product->name) ?? 'NOBI FASHION' }}"
                                    title="{{ $product?->primaryImage?->title ?? renderMeta($product->name) ?? 'NOBI FASHION' }}"
                                    class="nobifashion_single_info_images_main_image nobifashion_single_image_clickable"
                                    data-default-src="{{ asset('clients/assets/img/clothes/' . ($product?->primaryImage?->url ?? optional($galleryImages->first())->url ?? 'no-image.webp')) }}">

                                @if ($galleryImages->count() > 1)
                                    <button type="button" class="nobifashion_single_info_gallery_arrow" aria-label="Ảnh tiếp theo">
                                        &#10095;
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="nobifashion_single_info_gallery_social">
                            <span>Chia sẻ</span>
                            <a
                                class="fb-icon"
                                href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode(url()->current()) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Chia sẻ Facebook">
                                f
                            </a>
                        </div>

                        {{-- <div class="nobifashion_single_info_gallery_banner">
                            <div class="banner-badge">
                                {{ $product->isInFlashSale() ? 'Hot' : 'NOBI' }}<br>
                                <span>{{ $discountPercent ? '-' . $discountPercent . '%' : 'NEW' }}</span>
                            </div>
                            <div>
                                <div class="banner-text">{{ $product->isInFlashSale() ? 'FLASH SALE' : 'ƯU ĐÃI HÔM NAY' }}</div>
                                <div class="banner-discount">{{ $discountPercent ? '-' . $discountPercent . '%' : 'MỚI' }}</div>
                                <div class="banner-btn">{{ $voucherItems->isNotEmpty() ? 'Sao chép voucher ngay' : 'Chọn size và mua ngay' }}</div>
                            </div>
                        </div> --}}
                    </div>

                    <div class="nobifashion_single_info_detail nobifashion_single_info_specifications">
                        @if ($product->isInFlashSale() && $currentFlashSale)
                            <div class="nobifashion_single_info_flashsale nobifashion_single_info_specifications_deal">
                                <div class="nobifashion_single_info_specifications_label">
                                    ⚡ Săn deal
                                </div>
                                <div class="nobifashion_single_info_specifications_progress">
                                    <div class="nobifashion_single_info_specifications_progress_bar" style="width: {{ $flashSalePercent }}%;"></div>
                                </div>
                                <div class="nobifashion_single_info_specifications_time">
                                    <span class="nobifashion_single_info_specifications_end_time">Kết thúc trong</span>
                                    <div class="nobifashion_single_info_specifications_countdown">
                                        <div class="nobifashion_single_info_specifications_box nobifashion_single_info_specifications_box_days">00</div>
                                        <span>:</span>
                                        <div class="nobifashion_single_info_specifications_box nobifashion_single_info_specifications_box_house">00</div>
                                        <span>:</span>
                                        <div class="nobifashion_single_info_specifications_box nobifashion_single_info_specifications_box_minute">00</div>
                                        <span>:</span>
                                        <div class="nobifashion_single_info_specifications_box nobifashion_single_info_specifications_box_second">00</div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <h1 class="nobifashion_single_info_title">
                            {{ renderMeta($product->name) ?? 'Sản phẩm thời trang chính hãng - NOBI FASHION' }}
                        </h1>

                        <div class="nobifashion_single_info_meta">
                            <div class="nobifashion_single_info_sku">
                                Mã sản phẩm:
                                <span class="nobifashion_single_info_specifications_brand_code">{{ $product->sku }}</span>
                            </div>
                            @if($product->brand)
                                <div class="nobifashion_single_info_sku">
                                    Hãng:
                                    <span class="nobifashion_single_info_specifications_brand_code">{{ $product->brand->name }}</span>
                                </div>
                            @endif
                            <div class="nobifashion_single_info_meta_rating">
                                <span class="nobifashion_single_info_meta_stars">★★★★★</span>
                                <span class="nobifashion_single_info_meta_reviews" onclick="tabReview()">
                                    (<a href="#nobifashion_review">{{ rand(10, 1000) }} đánh giá</a>)
                                </span>
                            </div>
                        </div>

                        <div
                            class="nobifashion_single_info_price_block nobifashion_single_info_specifications_price"
                            data-base-current-price="{{ (int) $displayCurrentPrice }}"
                            data-base-original-price="{{ (int) ($displayOriginalPrice ?? 0) }}"
                            data-base-discount="{{ (int) ($discountPercent ?? 0) }}"
                            data-base-saved="{{ (int) $savedAmount }}">
                            <div class="nobifashion_single_info_price_row">
                                <span class="nobifashion_single_info_price_current nobifashion_single_info_specifications_new_price">
                                    {{ number_format($displayCurrentPrice, 0, ',', '.') }}đ
                                </span>
                                <span class="nobifashion_single_info_price_original nobifashion_single_info_specifications_old_price {{ $displayOriginalPrice ? '' : 'is-hidden' }}">
                                    {{ $displayOriginalPrice ? number_format($displayOriginalPrice, 0, ',', '.') . 'đ' : '' }}
                                </span>
                                <span class="nobifashion_single_info_price_badge {{ $discountPercent ? '' : 'is-hidden' }}">
                                    {{ $discountPercent ? '-' . $discountPercent . '%' : '' }}
                                </span>
                            </div>

                            <div class="nobifashion_single_info_saving {{ $savedAmount > 0 ? '' : 'is-hidden' }}">
                                <span>(Tiết kiệm:</span>
                                <span class="save-amount">{{ number_format($savedAmount, 0, ',', '.') }}đ</span>
                                <span class="voucher-link" @if ($voucherItems->isNotEmpty()) onclick="showPopupVoucher()" @endif>
                                    {{ $voucherItems->isNotEmpty() ? 'Giá sau voucher' : 'Ưu đãi hôm nay' }}
                                </span>
                                <span>)</span>
                            </div>
                        </div>

                        <div class="nobifashion_single_info_badges">
                            @if ($bestVoucherPrice !== null)
                                <span class="nobifashion_single_info_best_price">
                                    Giá tốt nhất: {{ number_format($bestVoucherPrice, 0, ',', '.') }}đ
                                </span>
                            @endif

                            @if (optional($product->created_at)->gte(now()->subDays(45)))
                                <span class="nobifashion_single_info_new_arrival">New Arrival</span>
                            @elseif ($product->is_featured ?? false)
                                <span class="nobifashion_single_info_new_arrival">Nổi bật</span>
                            @endif
                        </div>

                        @if ($voucherCards->isNotEmpty())
                            <div class="nobifashion_single_info_voucher_label">Mã giảm giá:</div>
                            <div class="nobifashion_single_info_voucher_list">
                                @foreach ($voucherCards as $voucherCard)
                                    <button
                                        type="button"
                                        class="nobifashion_single_info_voucher_tag"
                                        data-code="{{ $voucherCard['code'] }}"
                                        style="--voucher-accent: {{ $voucherCard['accent'] }}">
                                        <span class="tag-icon">{{ $voucherCard['icon'] }}</span>
                                        {{ $voucherCard['label'] }}
                                        <span class="voucher-code">{{ $voucherCard['code'] }}</span>
                                    </button>
                                @endforeach

                                @if ($voucherItems->count() > 5)
                                    <button type="button" class="nobifashion_single_info_voucher_more" onclick="showPopupVoucher()">
                                        &#8250;
                                    </button>
                                @endif
                            </div>
                        @endif

                        <div class="nobifashion_single_info_promo_box">
                            <div class="nobifashion_single_info_promo_title">
                                <span class="gift-icon">🎁</span>
                                {{ $currentFlashSale?->title ?? 'Ưu đãi dành riêng cho sản phẩm này' }}
                            </div>
                            <ul class="nobifashion_single_info_promo_list">
                                @forelse ($voucherItems->take(4) as $voucher)
                                    <li>
                                        @if (($voucher->type ?? '') === 'percentage')
                                            Mã <span class="highlight">{{ $voucher->code }}</span> giảm
                                            <span class="highlight">{{ number_format($voucher->value ?? 0, 0, ',', '.') }}%</span>
                                            @if (($voucher->min_order_amount ?? 0) > 0)
                                                cho đơn từ <span class="highlight">{{ number_format($voucher->min_order_amount, 0, ',', '.') }}đ</span>
                                            @endif
                                        @elseif (($voucher->type ?? '') === 'fixed_amount')
                                            Mã <span class="highlight">{{ $voucher->code }}</span> giảm trực tiếp
                                            <span class="highlight">{{ number_format($voucher->value ?? 0, 0, ',', '.') }}đ</span>
                                            @if (($voucher->min_order_amount ?? 0) > 0)
                                                cho đơn từ <span class="highlight">{{ number_format($voucher->min_order_amount, 0, ',', '.') }}đ</span>
                                            @endif
                                        @elseif (($voucher->type ?? '') === 'free_ship')
                                            Mã <span class="highlight">{{ $voucher->code }}</span> miễn phí giao hàng
                                            @if (($voucher->value ?? 0) > 0)
                                                tối đa <span class="highlight">{{ number_format($voucher->value, 0, ',', '.') }}đ</span>
                                            @endif
                                        @else
                                            Sao chép mã <span class="highlight">{{ $voucher->code }}</span> để nhận ưu đãi tốt hơn.
                                        @endif
                                    </li>
                                @empty
                                    <li>Miễn phí giao hàng toàn quốc cho đơn từ <span class="highlight">399.000đ</span>.</li>
                                    <li>Đổi size thuận tiện trong vòng <span class="highlight">30 ngày</span>.</li>
                                    <li>Ưu tiên hỗ trợ nhanh qua hotline <span class="highlight">{{ $settings->contact_phone ?? '1800 9203' }}</span>.</li>
                                    <li>Thanh toán linh hoạt và theo dõi đơn hàng dễ dàng ngay sau khi đặt.</li>
                                @endforelse
                            </ul>
                        </div>

                        @if ($variants->isNotEmpty())
                            @foreach ($attributesGrouped as $key => $values)
                                @php
                                    $normalizedKey = \Illuminate\Support\Str::lower($key);
                                    $label = $attributeLabels[$normalizedKey] ?? ucfirst($key);
                                    $optionClass = match ($normalizedKey) {
                                        'size' => 'size-option',
                                        'color' => 'color-option',
                                        'material', 'materials' => 'material-option',
                                        'weight' => 'weight-option',
                                        default => 'type-option',
                                    };
                                @endphp

                                @if ($normalizedKey === 'color')
                                    <div class="nobifashion_single_info_specifications_{{ $normalizedKey }} nobifashion_single_info_variant_group">
                                        <div class="nobifashion_single_info_color_label">
                                            {{ $label }}:
                                            <span id="selected-{{ $key }}">-</span>
                                        </div>
                                        <div class="nobifashion_single_info_color_list color-list">
                                            @foreach ($values as $val)
                                                @php
                                                    $stock = $variants
                                                        ->filter(function ($variantItem) use ($key, $val) {
                                                            $attrs = is_string($variantItem->attributes)
                                                                ? json_decode($variantItem->attributes, true)
                                                                : $variantItem->attributes;

                                                            return isset($attrs[$key]) && $attrs[$key] === $val;
                                                        })
                                                        ->sum('stock_quantity');
                                                    $isDisabled = $stock <= 0;
                                                @endphp
                                                <button
                                                    type="button"
                                                    class="nobifashion_single_info_color_swatch {{ $optionClass }}"
                                                    data-attr-key="{{ $key }}"
                                                    data-attr-value="{{ $val }}"
                                                    title="{{ $val }}"
                                                    aria-label="{{ $val }}"
                                                    style="background: {{ $resolveSwatchColor($val) }}"
                                                    {{ $isDisabled ? 'disabled' : '' }}>
                                                    <span class="nobifashion_single_info_color_swatch_text">{{ mb_substr($val, 0, 1) }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @elseif ($normalizedKey === 'size')
                                    <div class="nobifashion_single_info_specifications_{{ $normalizedKey }} nobifashion_single_info_variant_group">
                                        <div class="nobifashion_single_info_size_row">
                                            <span class="nobifashion_single_info_size_label">
                                                {{ $label }}:
                                                <span id="selected-{{ $key }}">-</span>
                                            </span>
                                            <a onclick="tabSizeGuide()" href="#nobifashion_main_tab_size_guide" class="nobifashion_single_info_size_guide">
                                                Tư vấn chọn size
                                            </a>
                                        </div>
                                        <div class="nobifashion_single_info_size_list size-list">
                                            @foreach ($values as $val)
                                                @php
                                                    $stock = $variants
                                                        ->filter(function ($variantItem) use ($key, $val) {
                                                            $attrs = is_string($variantItem->attributes)
                                                                ? json_decode($variantItem->attributes, true)
                                                                : $variantItem->attributes;

                                                            return isset($attrs[$key]) && $attrs[$key] === $val;
                                                        })
                                                        ->sum('stock_quantity');
                                                    $isDisabled = $stock <= 0;
                                                @endphp
                                                <button
                                                    type="button"
                                                    class="nobifashion_single_info_size_btn {{ $optionClass }}"
                                                    data-attr-key="{{ $key }}"
                                                    data-attr-value="{{ $val }}"
                                                    {{ $isDisabled ? 'disabled' : '' }}>
                                                    {{ $val }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="nobifashion_single_info_specifications_{{ $normalizedKey }} nobifashion_single_info_variant_group">
                                        <div class="nobifashion_single_info_option_label">
                                            {{ $label }}:
                                            <span id="selected-{{ $key }}">-</span>
                                        </div>
                                        <div class="nobifashion_single_info_option_list {{ $normalizedKey }}-list">
                                            @foreach ($values as $val)
                                                @php
                                                    $stock = $variants
                                                        ->filter(function ($variantItem) use ($key, $val) {
                                                            $attrs = is_string($variantItem->attributes)
                                                                ? json_decode($variantItem->attributes, true)
                                                                : $variantItem->attributes;

                                                            return isset($attrs[$key]) && $attrs[$key] === $val;
                                                        })
                                                        ->sum('stock_quantity');
                                                    $isDisabled = $stock <= 0;
                                                @endphp
                                                <button
                                                    type="button"
                                                    class="nobifashion_single_info_option_btn {{ $optionClass }}"
                                                    data-attr-key="{{ $key }}"
                                                    data-attr-value="{{ $val }}"
                                                    {{ $isDisabled ? 'disabled' : '' }}>
                                                    {{ $val }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <p class="nobifashion_single_info_specifications_no_variants">
                                Sản phẩm này hiện chưa có biến thể khả dụng.
                            </p>
                        @endif

                        <form class="nobifashion_single_info_specifications_actions" action="{{ route('client.cart.add') }}" method="POST">
                            @csrf

                            <div class="nobifashion_single_info_cart_row">
                                <div class="nobifashion_single_info_qty nobifashion_single_info_specifications_actions_qty">
                                    <button type="button" class="nobifashion_single_info_specifications_actions_btn" onclick="decreaseQty()">&#8722;</button>
                                    <span class="nobifashion_single_info_qty_value nobifashion_single_info_specifications_actions_value">1</span>
                                    <button type="button" class="nobifashion_single_info_specifications_actions_btn" onclick="increaseQty()">+</button>
                                </div>

                                <div class="nobifashion_single_info_action_primary">
                                    <button
                                        disabled
                                        type="submit"
                                        name="action"
                                        value="add_to_cart"
                                        class="nobifashion_single_info_add_cart nobifashion_single_info_specifications_actions_cart disabled">
                                        THÊM NGAY VÀO GIỎ
                                    </button>

                                    <button
                                        type="button"
                                        data-product-id="{{ $product->id }}"
                                        class="nobifashion_fav_btn nobifashion_single_info_favorite_btn {{ in_array($product->id, $favoriteProductIds ?? []) ? 'active' : '' }}"
                                        aria-label="Yêu thích">
                                        @if (in_array($product->id, $favoriteProductIds ?? []))
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="#ff0000" d="M305 151.1L320 171.8L335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1L576 231.7C576 343.9 436.1 474.2 363.1 529.9C350.7 539.3 335.5 544 320 544C304.5 544 289.2 539.4 276.9 529.9C203.9 474.2 64 343.9 64 231.7L64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1z"/></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path fill="#ff0000" d="M442.9 144C415.6 144 389.9 157.1 373.9 179.2L339.5 226.8C335 233 327.8 236.7 320.1 236.7C312.4 236.7 305.2 233 300.7 226.8L266.3 179.2C250.3 157.1 224.6 144 197.3 144C150.3 144 112.2 182.1 112.2 229.1C112.2 279 144.2 327.5 180.3 371.4C221.4 421.4 271.7 465.4 306.2 491.7C309.4 494.1 314.1 495.9 320.2 495.9C326.3 495.9 331 494.1 334.2 491.7C368.7 465.4 419 421.3 460.1 371.4C496.3 327.5 528.2 279 528.2 229.1C528.2 182.1 490.1 144 443.1 144zM335 151.1C360 116.5 400.2 96 442.9 96C516.4 96 576 155.6 576 229.1C576 297.7 533.1 358 496.9 401.9C452.8 455.5 399.6 502 363.1 529.8C350.8 539.2 335.6 543.9 320 543.9C304.4 543.9 289.2 539.2 276.9 529.8C240.4 502 187.2 455.5 143.1 402C106.9 358.1 64 297.7 64 229.1C64 155.6 123.6 96 197.1 96C239.8 96 280 116.5 305 151.1L320 171.8L335 151.1z"/></svg>
                                        @endif
                                    </button>
                                </div>
                            </div>

                            <button
                                disabled
                                type="submit"
                                name="action"
                                value="buy_now"
                                class="nobifashion_single_info_buy_now nobifashion_single_info_specifications_actions_buy disabled">
                                MUA NGAY
                            </button>
                        </form>

                        <div class="nobifashion_single_info_stock">
                            <div class="nobifashion_single_info_stock_bar_wrap">
                                <div
                                    id="product-stock-progress"
                                    class="nobifashion_single_info_stock_bar"
                                    data-base-width="{{ $defaultStockPercent }}"
                                    style="width: {{ $defaultStockPercent }}%;">
                                </div>
                                <span class="nobifashion_single_info_stock_bar_text" id="product-stock">
                                    @if ($variants->isNotEmpty())
                                        Chọn biến thể để xem tồn kho
                                    @else
                                        {{ $defaultStockValue > 0 ? 'Còn ' . $defaultStockValue . ' sản phẩm' : 'Tạm hết hàng' }}
                                    @endif
                                </span>
                            </div>
                            <div class="nobifashion_single_info_stock_note" id="product-stock-note" data-default-note="{{ $defaultStockNote }}">
                                {{ $defaultStockNote }}
                            </div>
                        </div>

                        <div class="nobifashion_single_info_services">
                            <div class="nobifashion_single_info_service_item">
                                <span class="svc-icon">📞</span>
                                <span>Tổng đài hỗ trợ nhanh {{ $settings->contact_phone ?? '1800 9203' }}</span>
                            </div>
                            <div class="nobifashion_single_info_service_item">
                                <span class="svc-icon">🔄</span>
                                <span>Đổi hàng nhanh chóng thuận tiện trong vòng 30 ngày.</span>
                            </div>
                            <div class="nobifashion_single_info_service_item">
                                <span class="svc-icon">🚚</span>
                                <span>Miễn phí giao hàng toàn quốc cho đơn từ 399.000đ.</span>
                            </div>
                            <div class="nobifashion_single_info_service_item">
                                <span class="svc-icon">✅</span>
                                <span>Thanh toán an toàn, xác nhận đơn và theo dõi trạng thái dễ dàng.</span>
                            </div>
                        </div>

                        <div class="nobifashion_single_info_support">
                            <div class="nobifashion_single_info_support_title">Cần tư vấn nhanh?</div>
                            <form class="nobifashion_single_info_images_support_form" id="phone-request-form" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <div class="nobifashion_single_info_images_support_form_group">
                                    <input
                                        type="text"
                                        placeholder="Nhập số điện thoại để được tư vấn nhanh"
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
                                    <p class="nobifashion_single_info_images_support_form_notice_text">
                                        Để lại số điện thoại, NOBI FASHION sẽ liên hệ và tư vấn cho bạn.
                                    </p>
                                    <div id="phone-request-message" style="display: none; margin-top: 10px; padding: 8px; border-radius: 4px; font-size: 13px;"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @if ($voucherItems->isNotEmpty())
                    <div id="voucherPopup" class="nobifashion_main_show_popup_voucher_overlay">
                        <div class="nobifashion_main_show_popup_voucher_box">
                            <button class="nobifashion_main_show_popup_voucher_close">&times;</button>
                            <h2>🎉 Mã ưu đãi dành cho bạn</h2>
                            <img width="100" src="{{ asset('clients/assets/img/other/party.gif') }}" alt="Voucher NOBI FASHION">
                            <p>Sao chép nhanh một trong các mã sau để dùng ngay khi thanh toán:</p>
                            @foreach ($voucherItems as $voucher)
                                <div class="nobifashion_main_show_popup_voucher_code">{{ $voucher->code }}</div>
                            @endforeach
                            <p>Ưu đãi sẽ được áp dụng theo điều kiện từng mã.</p>
                        </div>
                    </div>
                @else
                    <div id="voucherPopup" class="nobifashion_main_show_popup_voucher_overlay">
                        <div class="nobifashion_main_show_popup_voucher_box">
                            <button class="nobifashion_main_show_popup_voucher_close">&times;</button>
                        </div>
                    </div>
                @endif
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
