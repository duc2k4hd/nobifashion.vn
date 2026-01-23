@extends('clients.layouts.master')

@section('title', 'Chi tiết đơn hàng - ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION')))

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
                <a href="{{ route('client.order.index') }}">Đơn hàng của tôi</a>
                <span class="separator">>></span>
                <span class="breadcrumb-current">Chi tiết đơn hàng</span>
            </div>
        </section>

        <section class="nobifashion_order_detail">
            <div class="nobifashion_order_detail_container">
                <div class="nobifashion_order_detail_header">
                    <h1 class="nobifashion_order_detail_title">Chi tiết đơn hàng</h1>
                    <a href="{{ route('client.order.index') }}" class="nobifashion_order_detail_back">← Quay lại danh sách</a>
                </div>

                <!-- Order Info -->
                <div class="nobifashion_order_detail_card">
                    <h2 class="nobifashion_order_detail_card_title">Thông tin đơn hàng</h2>
                    <div class="nobifashion_order_detail_info">
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Mã đơn hàng:</span>
                            <span class="nobifashion_order_detail_info_value">{{ $order->code }}</span>
                        </div>
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Ngày đặt:</span>
                            <span class="nobifashion_order_detail_info_value">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Trạng thái đơn hàng:</span>
                            <span class="nobifashion_order_detail_info_value status-{{ $order->status }}">
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
                        </div>
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Trạng thái thanh toán:</span>
                            <span class="nobifashion_order_detail_info_value payment-{{ $order->payment_status }}">
                                @if($order->payment_status === 'pending')
                                    💳 Chờ thanh toán
                                @elseif($order->payment_status === 'paid')
                                    ✅ Đã thanh toán
                                @else
                                    ❌ Thanh toán thất bại
                                @endif
                            </span>
                        </div>
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Trạng thái giao hàng:</span>
                            <span class="nobifashion_order_detail_info_value delivery-{{ $order->delivery_status }}">
                                @if($order->delivery_status === 'pending')
                                    📦 Chờ giao hàng
                                @elseif($order->delivery_status === 'shipped')
                                    🚚 Đang giao hàng    
                                @elseif($order->delivery_status === 'delivered')
                                    ✅ Đã giao hàng
                                @elseif($order->delivery_status === 'returned')
                                    ↩️ Đã trả hàng
                                @elseif($order->delivery_status === 'cancelled')
                                    ↩️ Đã hủy hàng
                                @else
                                    ⏸️ Chưa xác định
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Receiver Info -->
                <div class="nobifashion_order_detail_card">
                    <h2 class="nobifashion_order_detail_card_title">Thông tin người nhận</h2>
                    <div class="nobifashion_order_detail_info">
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Họ tên:</span>
                            <span class="nobifashion_order_detail_info_value">{{ $order->receiver_name }}</span>
                        </div>
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Số điện thoại:</span>
                            <span class="nobifashion_order_detail_info_value">{{ $order->receiver_phone }}</span>
                        </div>
                        @if($order->receiver_email)
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Email:</span>
                            <span class="nobifashion_order_detail_info_value">{{ $order->receiver_email }}</span>
                        </div>
                        @endif
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Địa chỉ:</span>
                            <span class="nobifashion_order_detail_info_value">{{ $order->shipping_address }}</span>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="nobifashion_order_detail_card">
                    <h2 class="nobifashion_order_detail_card_title">Sản phẩm trong đơn</h2>
                    <div class="nobifashion_order_detail_items">
                        <table class="nobifashion_order_detail_table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="nobifashion_order_detail_table_product">
                                                @php
                                                    $imageUrl = $item->variant?->primaryVariantImage
                                                        ? asset('clients/assets/img/clothes/' . $item->variant->primaryVariantImage->url)
                                                        : ($item->product->primaryImage
                                                            ? asset('clients/assets/img/clothes/' . $item->product->primaryImage->url)
                                                            : asset('clients/assets/img/clothes/no-image.webp'));
                                                @endphp
                                                <img src="{{ $imageUrl }}" alt="{{ $item->product->name }}" class="nobifashion_order_detail_table_product_img">
                                                <div class="nobifashion_order_detail_table_product_info">
                                                    <div class="nobifashion_order_detail_table_product_name">{{ $item->product->name }}</div>
                                                    @if($item->variant)
                                                        @php
                                                            $attrs = is_string($item->variant->attributes) 
                                                                ? json_decode($item->variant->attributes, true) 
                                                                : $item->variant->attributes;
                                                        @endphp
                                                        @if($attrs && is_array($attrs))
                                                            <div class="nobifashion_order_detail_table_product_attrs">
                                                                {{ collect($attrs)->map(fn($val, $key) => ucfirst($key) . ': ' . $val)->join(', ') }}
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ number_format($item->price, 0, ',', '.') }} đ</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td><strong>{{ number_format($item->total_price, 0, ',', '.') }} đ</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="nobifashion_order_detail_card">
                    <h2 class="nobifashion_order_detail_card_title">Tóm tắt đơn hàng</h2>
                    <div class="nobifashion_order_detail_summary">
                        @php
                            // Tính lại tạm tính từ order_items (đảm bảo đúng với giá đã lưu)
                            $calculatedSubtotal = $order->items->sum('total_price');
                        @endphp
                        <div class="nobifashion_order_detail_summary_item">
                            <span class="nobifashion_order_detail_summary_label">Tạm tính:</span>
                            <span class="nobifashion_order_detail_summary_value">{{ number_format($calculatedSubtotal, 0, ',', '.') }} đ</span>
                        </div>
                        <div class="nobifashion_order_detail_summary_item">
                            <span class="nobifashion_order_detail_summary_label">Phí vận chuyển:</span>
                            <span class="nobifashion_order_detail_summary_value">{{ number_format($order->shipping_fee, 0, ',', '.') }} đ</span>
                        </div>
                        @if($order->tax > 0)
                        <div class="nobifashion_order_detail_summary_item">
                            <span class="nobifashion_order_detail_summary_label">Thuế:</span>
                            <span class="nobifashion_order_detail_summary_value">{{ number_format($order->tax, 0, ',', '.') }} đ</span>
                        </div>
                        @endif
                        @if($order->discount > 0)
                        <div class="nobifashion_order_detail_summary_item">
                            <span class="nobifashion_order_detail_summary_label">Giảm giá:</span>
                            <span class="nobifashion_order_detail_summary_value">-{{ number_format($order->discount, 0, ',', '.') }} đ</span>
                        </div>
                        @endif
                        @if($order->voucher_discount > 0)
                        <div class="nobifashion_order_detail_summary_item">
                            <span class="nobifashion_order_detail_summary_label">Giảm giá voucher ({{ $order->voucher_code }}):</span>
                            <span class="nobifashion_order_detail_summary_value">-{{ number_format($order->voucher_discount, 0, ',', '.') }} đ</span>
                        </div>
                        @endif
                        <div class="nobifashion_order_detail_summary_item nobifashion_order_detail_summary_total">
                            <span class="nobifashion_order_detail_summary_label">Tổng cộng:</span>
                            <span class="nobifashion_order_detail_summary_value">{{ number_format($order->final_price, 0, ',', '.') }} đ</span>
                        </div>
                    </div>
                </div>

                <!-- Payment & Shipping Info -->
                <div class="nobifashion_order_detail_card">
                    <h2 class="nobifashion_order_detail_card_title">Thanh toán & Vận chuyển</h2>
                    <div class="nobifashion_order_detail_info">
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Phương thức thanh toán:</span>
                            <span class="nobifashion_order_detail_info_value">
                                @if($order->payment_method === 'cod')
                                    💵 Thanh toán khi nhận hàng (COD)
                                @elseif($order->payment_method === 'bank_transfer')
                                    🏦 Chuyển khoản ngân hàng
                                @elseif($order->payment_method === 'momo')
                                    💜 MoMo
                                @elseif($order->payment_method === 'zalopay')
                                    💙 ZaloPay
                                @elseif($order->payment_method === 'payos')
                                    💳 PayOS
                                @else
                                    {{ $order->payment_method }}
                                @endif
                            </span>
                        </div>
                        @if($order->transaction_code)
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Mã giao dịch:</span>
                            <span class="nobifashion_order_detail_info_value">{{ $order->transaction_code }}</span>
                        </div>
                        @endif
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Đơn vị vận chuyển:</span>
                            <span class="nobifashion_order_detail_info_value">
                                @if($order->shipping_partner === 'ghn')
                                    🚚 Giao Hàng Nhanh (GHN)
                                @elseif($order->shipping_partner === 'viettelpost')
                                    📮 ViettelPost
                                @elseif($order->shipping_partner === 'ghtk')
                                    📦 GHTK
                                @else
                                    {{ $order->shipping_partner ?? 'Chưa xác định' }}
                                @endif
                            </span>
                        </div>
                        @if($order->shipping_tracking_code)
                        <div class="nobifashion_order_detail_info_item">
                            <span class="nobifashion_order_detail_info_label">Mã vận đơn:</span>
                            <span class="nobifashion_order_detail_info_value">{{ $order->shipping_tracking_code }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                @php
                    $ghnStatuses = config('ghn.shipping_statuses', []);
                    $shippingRaw = $order->shipping_raw_response ?? [];
                    $ghnPayload = $shippingRaw['ghn'] ?? $shippingRaw;
                    $clientShippingHistory = collect($shippingRaw['status_history'] ?? [])->sortBy(function ($item) {
                        return $item['created_at'] ?? now();
                    })->values();
                    $currentShippingStatusKey = $shippingRaw['current_status'] ?? null;
                    $currentShippingStatus = $currentShippingStatusKey
                        ? array_merge(['status' => $currentShippingStatusKey], $ghnStatuses[$currentShippingStatusKey] ?? [])
                        : null;
                @endphp

                @if($order->shipping_partner === 'ghn' && ($clientShippingHistory->count() || $currentShippingStatus))
                    <div class="nobifashion_order_detail_card nobifashion_order_tracking_card">
                        <div class="nobifashion_order_detail_card_header">
                            <h2 class="nobifashion_order_detail_card_title">Theo dõi trạng thái vận chuyển (GHN)</h2>
                            @if($order->shipping_tracking_code)
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="nobifashion_order_tracking_code">Mã vận đơn: {{ $order->shipping_tracking_code }}</span>
                                    <a href="{{ route('client.order.track', ['tracking_code' => $order->shipping_tracking_code]) }}" class="nobifashion_order_detail_btn nobifashion_order_detail_btn_secondary">
                                        🔍 Tra cứu trực tuyến
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="nobifashion_order_tracking_status_current">
                            @if($currentShippingStatus)
                                <div class="nobifashion_order_tracking_status_label">
                                    Trạng thái hiện tại:
                                    <span>{{ $currentShippingStatus['label'] ?? strtoupper($currentShippingStatus['status']) }}</span>
                                </div>
                                @if(!empty($currentShippingStatus['description']))
                                    <p>{{ $currentShippingStatus['description'] }}</p>
                                @endif
                            @else
                                <div class="nobifashion_order_tracking_status_label">
                                    Đơn hàng đang chờ GHN cập nhật trạng thái mới.
                                </div>
                            @endif
                        </div>

                        <div class="nobifashion_order_tracking_body">
                            <div class="nobifashion_order_tracking_timeline_wrapper">
                                <ul class="nobifashion_order_timeline">
                                    @forelse($clientShippingHistory as $log)
                                        <li class="nobifashion_order_timeline_item {{ $loop->last ? 'is-active' : '' }}">
                                            <div class="nobifashion_order_timeline_point"></div>
                                            <div class="nobifashion_order_timeline_content">
                                                <div class="nobifashion_order_timeline_title">
                                                    {{ $log['label'] ?? strtoupper($log['status'] ?? '') }}
                                                </div>
                                                <div class="nobifashion_order_timeline_meta">
                                                    {{ \Carbon\Carbon::parse($log['created_at'] ?? now())->format('d/m/Y H:i') }}
                                                    @if(!empty($log['created_by']))
                                                        • {{ $log['created_by'] }}
                                                    @endif
                                                </div>
                                                @if(!empty($log['description']))
                                                    <div class="nobifashion_order_timeline_desc">
                                                        {{ $log['description'] }}
                                                    </div>
                                                @endif
                                                @if(!empty($log['note']))
                                                    <div class="nobifashion_order_timeline_note">
                                                        <strong>Ghi chú:</strong> {{ $log['note'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        </li>
                                    @empty
                                        <li class="nobifashion_order_timeline_item">
                                            <div class="nobifashion_order_timeline_content">
                                                <div class="nobifashion_order_timeline_desc">
                                                    GHN chưa có cập nhật nào cho đơn hàng này.
                                                </div>
                                            </div>
                                        </li>
                                    @endforelse
                                </ul>
                            </div>
                            @if(!empty($ghnPayload['expected_delivery_time']))
                                <div class="nobifashion_order_tracking_expected">
                                    Dự kiến giao: {{ \Carbon\Carbon::parse($ghnPayload['expected_delivery_time'])->format('d/m/Y H:i') }}
                                </div>
                            @endif
                            @if(!empty($ghnPayload['total_fee']))
                                <div class="nobifashion_order_tracking_expected">
                                    Phí GHN: {{ number_format($ghnPayload['total_fee'], 0, ',', '.') }} đ
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($order->customer_note)
                <div class="nobifashion_order_detail_card">
                    <h2 class="nobifashion_order_detail_card_title">Ghi chú</h2>
                    <div class="nobifashion_order_detail_note">
                        {{ $order->customer_note }}
                    </div>
                </div>
                @endif

                <div class="nobifashion_order_detail_actions">
                    <a href="{{ route('client.order.index') }}" class="nobifashion_order_detail_btn nobifashion_order_detail_btn_back">← Quay lại danh sách</a>
                </div>
            </div>
        </section>
    </div>

    @include('clients.templates.chat')
@endsection

