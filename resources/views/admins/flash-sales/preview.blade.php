<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $flashSale->title }} | {{ $settings->site_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/flash-sale-icon.png') }}" type="image/x-icon">
    <style>
        body {
            background: #f5f7fa;
            padding: 20px;
        }
        .preview-header {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-4px);
        }
        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .product-info {
            padding: 16px;
        }
        .product-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: #0f172a;
        }
        .product-price {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
        }
        .original-price {
            color: #94a3b8;
            text-decoration: line-through;
        }
        .sale-price {
            color: #ef4444;
            font-weight: bold;
            font-size: 18px;
        }
        .discount-badge {
            background: #fee2e2;
            color: #b91c1c;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .stock-info {
            font-size: 12px;
            color: #64748b;
        }
        .countdown {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="preview-header">
            <h1>{{ $flashSale->title }}</h1>
            @if($flashSale->tag)
                <span class="badge bg-primary">{{ $flashSale->tag }}</span>
            @endif
            @if($flashSale->description)
                <p class="mt-2">{{ $flashSale->description }}</p>
            @endif
            @php
                $now = now();
                $isActive = $flashSale->is_active 
                    && $flashSale->status === 'active' 
                    && $flashSale->start_time <= $now 
                    && $flashSale->end_time >= $now;
            @endphp
            @if($isActive)
                <div class="countdown" id="countdown">
                    Còn lại: <span id="countdown-text"></span>
                </div>
            @elseif($flashSale->start_time > $now)
                <div class="countdown" style="background:#fef3c7;color:#92400e;">
                    ⏰ Sẽ bắt đầu: {{ $flashSale->start_time->format('d/m/Y H:i') }}
                </div>
            @else
                <div class="countdown" style="background:#fee2e2;color:#b91c1c;">
                    ❌ Flash Sale đã kết thúc
                </div>
            @endif
        </div>

        @if($flashSale->banner)
            <div style="margin-bottom:20px;">
                <img src="{{ asset($flashSale->banner) }}" alt="Banner" style="width:100%;border-radius:10px;">
            </div>
        @endif

        <div class="products-grid">
            @forelse($flashSale->items as $item)
                @if($item->product && $item->is_active && ($item->stock > $item->sold))
                    @php
                        $originalPrice = $item->original_price ?? $item->product->price ?? 0;
                        $salePrice = $item->sale_price ?? 0;
                        $discountPercent = $originalPrice > 0 ? round((1 - $salePrice / $originalPrice) * 100) : 0;
                        $sold = $item->sold ?? 0;
                        $stock = $item->stock ?? 0;
                        $remaining = max(0, $stock - $sold);
                        $percentSold = $stock > 0 ? ($sold / $stock) * 100 : 0;
                    @endphp
                    <div class="product-card">
                        @if($item->product->primaryImage)
                            <img src="{{ asset('clients/assets/img/clothes/' . $item->product->primaryImage->url) }}" 
                                 alt="{{ $item->product->name }}" 
                                 class="product-image">
                        @else
                            <div class="product-image" style="background:#e2e8f0;display:flex;align-items:center;justify-content:center;">
                                <small>No Image</small>
                            </div>
                        @endif
                        <div class="product-info">
                            <div class="product-name">{{ $item->product->name }}</div>
                            @if($item->product->primaryCategory)
                                <div style="font-size:11px;color:#64748b;margin-bottom:4px;">
                                    📂 {{ $item->product->primaryCategory->name }}
                                </div>
                            @endif
                            <div class="product-price">
                                <span class="original-price">{{ number_format($originalPrice, 0, ',', '.') }}₫</span>
                                <span class="sale-price">{{ number_format($salePrice, 0, ',', '.') }}₫</span>
                                @if($discountPercent > 0)
                                    <span class="discount-badge">-{{ $discountPercent }}%</span>
                                @endif
                            </div>
                            <div class="stock-info" style="margin-top:8px;">
                                @if($remaining <= 0)
                                    <span style="color:#b91c1c;font-weight:bold;">⚠️ HẾT HÀNG</span>
                                @elseif($sold < 5 && $remaining > 0)
                                    <span style="color:#e53935;font-weight:bold;">🔥 ĐANG BÁN CHẠY</span>
                                @elseif($percentSold >= 90 && $remaining > 0)
                                    <span style="color:#ff9800;font-weight:bold;">⚠️ SẮP HẾT HÀNG</span>
                                @else
                                    <span style="color:#423d3d;">Đã bán {{ $sold }} / Còn lại {{ $remaining }}</span>
                                @endif
                            </div>
                            @if($stock > 0)
                                <div style="margin-top:8px;">
                                    <div style="width:100%;height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;">
                                        <div style="height:100%;background:#10b981;width:{{ min(100, round($percentSold)) }}%;transition:width 0.3s;"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @empty
                <div style="grid-column:1/-1;text-align:center;padding:40px;color:#94a3b8;">
                    Chưa có sản phẩm nào trong Flash Sale này
                </div>
            @endforelse
        </div>
    </div>

    @php
        $now = now();
        $isActive = $flashSale->is_active 
            && $flashSale->status === 'active' 
            && $flashSale->start_time <= $now 
            && $flashSale->end_time >= $now;
    @endphp
    @if($isActive)
    <script>
        const endTime = new Date('{{ $flashSale->end_time->timezone(config('app.timezone', 'Asia/Ho_Chi_Minh'))->toIso8601String() }}').getTime();
        const countdownText = document.getElementById('countdown-text');
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = endTime - now;
            
            if (distance < 0) {
                countdownText.textContent = 'Đã kết thúc';
                if (countdownText.parentElement) {
                    countdownText.parentElement.style.background = '#fee2e2';
                    countdownText.parentElement.style.color = '#b91c1c';
                }
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            countdownText.textContent = `${days} ngày ${hours} giờ ${minutes} phút ${seconds} giây`;
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
    @endif
</body>
</html>

