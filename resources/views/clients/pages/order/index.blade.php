@extends('clients.layouts.master')

@section('title', 'Đơn hàng của tôi | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

@section('head')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('clients/assets/css/order.css') }}">
    <meta name="robots" content="follow, noindex"/>
@endsection

@section('content')
    <div class="nobifashion_order_wrapper">
        <!-- Breadcrumb -->
        <section>
            <div class="nobifashion_order_breadcrumb">
                <a href="{{ route('client.home.index') }}">Trang chủ</a>
                <span class="separator">>></span>
                <span class="breadcrumb-current">Đơn hàng của tôi</span>
            </div>
        </section>

        <section class="nobifashion_order_list">
            <div class="nobifashion_order_list_container">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h1 class="nobifashion_order_list_title mb-0">Đơn hàng của tôi</h1>
                    <a href="{{ route('client.order.track') }}" class="nobifashion_order_item_btn nobifashion_order_item_btn_view" style="text-decoration:none;">
                        🔍 Tra cứu vận đơn GHN
                    </a>
                </div>

                <!-- Filters -->
                <div class="nobifashion_order_filters">
                    <form method="GET" action="{{ route('client.order.index') }}" class="nobifashion_order_filter_form">
                        <div class="nobifashion_order_filter_group">
                            <label>Trạng thái đơn hàng:</label>
                            <select name="status" class="nobifashion_order_filter_select">
                                <option value="">Tất cả</option>
                                <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                                <option value="processing" {{ $filters['status'] === 'processing' ? 'selected' : '' }}>Đang xử lý</option>
                                <option value="completed" {{ $filters['status'] === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                                <option value="cancelled" {{ $filters['status'] === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                            </select>
                        </div>
                        <div class="nobifashion_order_filter_group">
                            <label>Trạng thái thanh toán:</label>
                            <select name="payment_status" class="nobifashion_order_filter_select">
                                <option value="">Tất cả</option>
                                <option value="pending" {{ $filters['payment_status'] === 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                                <option value="paid" {{ $filters['payment_status'] === 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                                <option value="failed" {{ $filters['payment_status'] === 'failed' ? 'selected' : '' }}>Thất bại</option>
                            </select>
                        </div>
                        <button type="submit" class="nobifashion_order_filter_btn">Lọc</button>
                        <a href="{{ route('client.order.index') }}" class="nobifashion_order_filter_reset">Xóa bộ lọc</a>
                    </form>
                </div>

                <!-- Orders List -->
                @if($orders->count() > 0)
                    <div class="nobifashion_order_items">
                        @foreach($orders as $order)
                            <div class="nobifashion_order_item">
                                <div class="nobifashion_order_item_header">
                                    <div class="nobifashion_order_item_info">
                                        <h3 class="nobifashion_order_item_code">
                                            Đơn hàng: <strong>{{ $order->code }}</strong>
                                        </h3>
                                        <div class="nobifashion_order_item_meta">
                                            <span class="nobifashion_order_item_date">
                                                📅 {{ $order->created_at->format('d/m/Y H:i') }}
                                            </span>
                                            <span class="nobifashion_order_item_status status-{{ $order->status }}">
                                                @if($order->status === 'pending')
                                                    ⏳ Chờ xử lý
                                                @elseif($order->status === 'processing')
                                                    🔄 Đang xử lý
                                                @elseif($order->status === 'completed')
                                                    ✅ Hoàn thành
                                                @else
                                                    ❌ Đã hủy
                                                @endif
                                            </span>
                                            <span class="nobifashion_order_item_payment payment-{{ $order->payment_status }}">
                                                @if($order->payment_status === 'pending')
                                                    💳 Chờ thanh toán
                                                @elseif($order->payment_status === 'paid')
                                                    ✅ Đã thanh toán
                                                @else
                                                    ❌ Thanh toán thất bại
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="nobifashion_order_item_total">
                                        <strong>{{ number_format($order->final_price, 0, ',', '.') }} đ</strong>
                                    </div>
                                </div>

                                <div class="nobifashion_order_item_products">
                                    @foreach($order->items->take(3) as $item)
                                        <div class="nobifashion_order_item_product">
                                            @php
                                                $imageUrl = $item->variant?->primaryVariantImage
                                                    ? asset('clients/assets/img/clothes/' . $item->variant->primaryVariantImage->url)
                                                    : ($item->product->primaryImage
                                                        ? asset('clients/assets/img/clothes/' . $item->product->primaryImage->url)
                                                        : asset('clients/assets/img/clothes/no-image.webp'));
                                            @endphp
                                            <img src="{{ $imageUrl }}" alt="{{ $item->product->name }}" class="nobifashion_order_item_product_img">
                                            <div class="nobifashion_order_item_product_info">
                                                <div class="nobifashion_order_item_product_name">{{ $item->product->name }}</div>
                                                @if($item->variant)
                                                    @php
                                                        $attrs = is_string($item->variant->attributes) 
                                                            ? json_decode($item->variant->attributes, true) 
                                                            : $item->variant->attributes;
                                                    @endphp
                                                    @if($attrs && is_array($attrs))
                                                        <div class="nobifashion_order_item_product_attrs">
                                                            {{ collect($attrs)->map(fn($val, $key) => ucfirst($key) . ': ' . $val)->join(', ') }}
                                                        </div>
                                                    @endif
                                                @endif
                                                <div class="nobifashion_order_item_product_qty">
                                                    Số lượng: {{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }} đ
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    @if($order->items->count() > 3)
                                        <div class="nobifashion_order_item_product_more">
                                            + {{ $order->items->count() - 3 }} sản phẩm khác
                                        </div>
                                    @endif
                                </div>

                                <div class="nobifashion_order_item_actions">
                                    <a href="{{ route('client.order.show', $order->id) }}" class="nobifashion_order_item_btn nobifashion_order_item_btn_view">
                                        👁️ Xem chi tiết
                                    </a>
                                    @if($order->shipping_partner === 'ghn' && $order->shipping_tracking_code)
                                        <a href="{{ route('client.order.track', ['tracking_code' => $order->shipping_tracking_code]) }}" class="nobifashion_order_item_btn nobifashion_order_item_btn_secondary">
                                            📦 Tra cứu GHN
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="nobifashion_order_pagination">
                        {{ $orders->links() }}
                    </div>
                @else
                    <div class="nobifashion_order_empty">
                        <div class="nobifashion_order_empty_icon">📦</div>
                        <h2>Chưa có đơn hàng nào</h2>
                        <p>Bạn chưa có đơn hàng nào. Hãy mua sắm ngay để có đơn hàng đầu tiên!</p>
                        <a href="{{ route('client.product.shop.index') }}" class="nobifashion_order_empty_btn">🛒 Mua sắm ngay</a>
                    </div>
                @endif
            </div>
        </section>
    </div>

    @include('clients.templates.chat')
@endsection

