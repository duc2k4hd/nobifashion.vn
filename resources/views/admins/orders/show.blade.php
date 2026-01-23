@extends('admins.layouts.master')

@section('title', 'Chi tiết đơn hàng')
@section('page-title', '📦 Chi tiết đơn hàng')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/order-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .card {
            background:#fff;
            border-radius:10px;
            padding:16px;
            box-shadow:0 1px 6px rgba(15,23,42,0.06);
            margin-bottom:16px;
        }
        .card > h3 {
            margin:0 0 12px;
            font-size:16px;
            font-weight:600;
            color:#0f172a;
        }
        .info-grid {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
            gap:12px;
        }
        .info-item {
            padding:8px 0;
            border-bottom:1px solid #eef2f7;
        }
        .info-label {
            font-size:12px;
            color:#64748b;
            margin-bottom:4px;
        }
        .info-value {
            font-size:14px;
            font-weight:500;
            color:#0f172a;
        }
        .items-table {
            width:100%;
            border-collapse:collapse;
            background:#fff;
        }
        .items-table th, .items-table td {
            padding:10px;
            border-bottom:1px solid #eef2f7;
            text-align:left;
            font-size:13px;
        }
        .items-table th {
            background:#f8fafc;
            font-weight:600;
            color:#475569;
        }
        .product-image {
            width:60px;
            height:60px;
            object-fit:cover;
            border-radius:6px;
        }
        .badge {
            padding:3px 9px;
            border-radius:999px;
            font-size:11px;
            font-weight:600;
        }
        .badge-pending { background:#fef3c7;color:#92400e;}
        .badge-processing { background:#dbeafe;color:#1d4ed8;}
        .badge-completed { background:#dcfce7;color:#15803d;}
        .badge-cancelled { background:#fee2e2;color:#b91c1c;}
        .badge-paid { background:#dcfce7;color:#15803d;}
        .badge-failed { background:#fee2e2;color:#b91c1c;}
        .summary-item {
            display:flex;
            justify-content:space-between;
            padding:8px 0;
            border-bottom:1px solid #eef2f7;
        }
        .summary-item.total {
            font-weight:600;
            font-size:16px;
            border-top:2px solid #eef2f7;
            margin-top:8px;
            padding-top:12px;
        }
        .ghn-modal-overlay {
            position:fixed;
            inset:0;
            background:rgba(15,23,42,0.65);
            display:flex;
            justify-content:center;
            align-items:center;
            padding:20px;
            z-index:1050;
            opacity:0;
            pointer-events:none;
            transition:opacity .25s ease;
        }
        .ghn-modal-overlay.is-active {
            opacity:1;
            pointer-events:auto;
        }
        .ghn-modal {
            width:100%;
            max-width:640px;
            max-height:90vh;
            background:#fff;
            border-radius:18px;
            box-shadow:0 35px 80px rgba(15,23,42,0.35);
            overflow:hidden;
            display:flex;
            flex-direction:column;
            transform:translateY(10px);
            animation:ghn-modal-in .25s ease forwards;
        }
        .ghn-modal-header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:20px 24px 12px;
            border-bottom:1px solid #eef2f7;
        }
        .ghn-modal-header h4 {
            margin:0;
            font-size:18px;
            font-weight:700;
            color:#0f172a;
        }
        .ghn-modal-close {
            border:none;
            background:transparent;
            font-size:24px;
            color:#94a3b8;
            cursor:pointer;
            transition:color .2s ease;
            line-height:1;
        }
        .ghn-modal-close:hover {
            color:#0f172a;
        }
        .ghn-modal-body {
            padding:20px 24px 24px;
            overflow:auto;
        }
        .ghn-modal-actions {
            display:flex;
            gap:10px;
            margin-top:8px;
            flex-wrap:wrap;
        }
        body.ghn-modal-open {
            overflow:hidden;
        }
        @keyframes ghn-modal-in {
            from { opacity:0; transform:translateY(15px); }
            to { opacity:1; transform:translateY(0); }
        }
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">Đơn hàng: {{ $order->code }}</h2>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">↩️ Quay lại</a>
                @if($order->status === 'completed' && $order->delivery_status === 'delivered' && $order->payment_status === 'paid')
                    <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn btn-info">🧾 In hóa đơn</a>
                    <a href="{{ route('admin.orders.invoice.pdf', $order) }}" class="btn btn-success">⬇️ PDF hóa đơn</a>
                @endif
                @if($order->shipping_partner === 'ghn' && $order->shipping_tracking_code)
                    <a href="{{ route('admin.orders.track', ['tracking_code' => $order->shipping_tracking_code]) }}" class="btn btn-outline-primary">🔍 Tra cứu GHN</a>
                @endif
                @if(!in_array($order->status, ['completed', 'cancelled']))
                    <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-primary">✏️ Sửa</a>
                @endif
                @if($order->canCancel())
                    <form action="{{ route('admin.orders.cancel', $order) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-danger">❌ Hủy đơn</button>
                    </form>
                @elseif($order->status === 'completed')
                    <a style="pointer-events: none" disabled href="javascript:void(0)" class="btn btn-success">✅ Đã hoàn thành</a>
                @else
                    <a style="pointer-events: none" disabled href="javascript:void(0)" class="btn btn-danger">❌ Đã hủy</a>
                @endif
                @if($order->status === 'pending')
                    <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn chấp nhận đơn hàng này?');">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="processing">
                        <button type="submit" class="btn btn-success">✅ Chấp nhận đơn hàng</button>
                    </form>
                @endif
                @if($order->status === 'processing' && $order->delivery_status === 'delivered')
                    <form action="{{ route('admin.orders.complete', $order) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success">✅ Hoàn thành</button>
                    </form>
                @endif
                @if($order->canCreateGhnShipment())
                <button type="button" onclick="showGhnModal()" class="btn btn-info">🚚 Lên đơn GHN</button>
                @endif
            </div>
        </div>

        @php
            $shippingRaw = $order->shipping_raw_response ?? [];
            $ghnData = $shippingRaw['ghn'] ?? [];
            $ghnPayload = $ghnData ?: $shippingRaw;
            $currentShippingStatus = $order->current_shipping_status_meta;
            $shippingHistory = collect($order->shipping_status_history)->sortByDesc(fn ($item) => $item['created_at'] ?? now())->values();
        @endphp

        <!-- Modal tạo đơn GHN -->
        @if($order->canCreateGhnShipment())
            <div id="ghn-create-order-modal" class="ghn-modal-overlay" aria-hidden="true" role="dialog">
                <div class="ghn-modal">
                    <div class="ghn-modal-header">
                        <h4>🚚 Tạo đơn hàng GHN</h4>
                        <button type="button" class="ghn-modal-close" aria-label="Đóng" onclick="hideGhnModal()">×</button>
                    </div>
                    <div class="ghn-modal-body">
                        <form action="{{ route('admin.orders.create-ghn', $order) }}" method="POST" id="create-ghn-order-form">
                            @csrf
                            <div style="display:flex;flex-direction:column;gap:16px;">
                                <div>
                                    <label for="pick-shift-id" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Ca lấy hàng <span style="color:red;">*</span></label>
                                    <select name="pick_shift_id" id="pick-shift-id" class="form-control" required>
                                        <option value="">-- Đang tải ca lấy hàng... --</option>
                                    </select>
                                    <small style="color:#64748b;font-size:11px;">Vui lòng chọn ca lấy hàng phù hợp</small>
                                    @error('pick_shift_id')
                                        <span class="text-danger" style="font-size:12px;">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Người trả phí vận chuyển --}}
                                <div>
                                    <label style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">
                                        Người trả phí vận chuyển
                                    </label>
                                    <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
                                        @php
                                            $defaultPayer = $order->payment_method === 'cod' ? 'receiver' : 'seller';
                                            $shippingPayer = old('shipping_payer', $defaultPayer);
                                        @endphp
                                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#334155;margin-bottom:4px;cursor:pointer;">
                                            <input type="radio"
                                                   name="shipping_payer"
                                                   value="receiver"
                                                   {{ $shippingPayer === 'receiver' ? 'checked' : '' }}>
                                            <span>Người nhận trả ship (GHN thu phí người nhận)</span>
                                        </label>
                                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#334155;margin-bottom:4px;cursor:pointer;">
                                            <input type="radio"
                                                   name="shipping_payer"
                                                   value="seller"
                                                   {{ $shippingPayer === 'seller' ? 'checked' : '' }}>
                                            <span>Người bán trả ship (Shop thanh toán phí với GHN)</span>
                                        </label>
                                    </div>
                                    <small style="color:#64748b;font-size:11px;display:block;margin-top:2px;">
                                        Tùy chọn này chỉ ảnh hưởng đến việc GHN thu phí từ ai (shop hay người nhận), 
                                        không tự động thay đổi giá đơn hàng. Hãy đảm bảo chính sách phí ship trong đơn hàng đã chính xác.
                                    </small>
                                </div>

                                <div>
                                    <label for="required-note" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Ghi chú yêu cầu</label>
                                    <select name="required_note" id="required-note" class="form-control">
                                        <option value="KHONGCHOXEMHANG" {{ old('required_note', 'KHONGCHOXEMHANG') === 'KHONGCHOXEMHANG' ? 'selected' : '' }}>Không cho xem hàng</option>
                                        <option value="CHOXEMHANGKHONGTHU" {{ old('required_note') === 'CHOXEMHANGKHONGTHU' ? 'selected' : '' }}>Cho xem hàng, không cho thử</option>
                                        <option value="CHOTHUHANG" {{ old('required_note') === 'CHOTHUHANG' ? 'selected' : '' }}>Cho thử hàng</option>
                                    </select>
                                </div>

                                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;">
                                    <div>
                                        <label for="weight" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Khối lượng (gram)</label>
                                        <input type="number" name="weight" id="weight" class="form-control" min="200" value="{{ old('weight') }}" placeholder="Tự động tính">
                                    </div>
                                    <div>
                                        <label for="insurance-value" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Giá trị bảo hiểm (VNĐ)</label>
                                        <input type="number" name="insurance_value" id="insurance-value" class="form-control" min="0" value="{{ old('insurance_value', $order->final_price) }}" placeholder="Tự động">
                                    </div>
                                </div>

                                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                                    <div>
                                        <label for="length" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Chiều dài (cm)</label>
                                        <input type="number" name="length" id="length" class="form-control" min="1" value="{{ old('length', 10) }}" placeholder="10">
                                    </div>
                                    <div>
                                        <label for="width" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Chiều rộng (cm)</label>
                                        <input type="number" name="width" id="width" class="form-control" min="1" value="{{ old('width', 10) }}" placeholder="10">
                                    </div>
                                    <div>
                                        <label for="height" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Chiều cao (cm)</label>
                                        <input type="number" name="height" id="height" class="form-control" min="1" value="{{ old('height', 10) }}" placeholder="10">
                                    </div>
                                </div>

                                <div class="ghn-modal-actions">
                                    <button type="submit" class="btn btn-info" style="font-size:13px;" onclick="return confirm('Bạn có chắc muốn tạo đơn GHN cho đơn hàng này?');">🚚 Tạo đơn GHN</button>
                                    <button type="button" class="btn btn-secondary" style="font-size:13px;" onclick="hideGhnModal()">Đóng</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <h3>Thông tin đơn hàng</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Mã đơn hàng</div>
                    <div class="info-value"><strong>{{ $order->code }}</strong></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Người đặt</div>
                    <div class="info-value">
                        @if($order->account)
                            {{ $order->account->name ?? $order->account->email }}
                        @else
                            Khách ({{ substr($order->session_id ?? '', 0, 16) }}...)
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Trạng thái</div>
                    <div class="info-value">
                        <span class="badge badge-{{ $order->status }}">
                            @if($order->status === 'pending') Chờ xử lý
                            @elseif($order->status === 'processing') Đang xử lý
                            @elseif($order->status === 'completed') Hoàn thành
                            @else Đã hủy
                            @endif
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Thanh toán</div>
                    <div class="info-value">
                        <span class="badge badge-{{ $order->payment_status }}">
                            @if($order->payment_status === 'pending') Chờ thanh toán
                            @elseif($order->payment_status === 'paid') Đã thanh toán
                            @else Thất bại
                            @endif
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Vận chuyển</div>
                    <div class="info-value">
                        @if($order->delivery_status === 'pending') Chờ giao
                        @elseif($order->delivery_status === 'shipped') Đang giao hàng
                        @elseif($order->delivery_status === 'delivered') Đã giao hàng
                        @elseif($order->delivery_status === 'cancelled') Đã hủy hàng
                        @else Đã trả hàng
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Ngày tạo</div>
                    <div class="info-value">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Cập nhật</div>
                    <div class="info-value">{{ $order->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Thông tin người nhận</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Họ tên</div>
                    <div class="info-value">{{ $order->receiver_name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Số điện thoại</div>
                    <div class="info-value">{{ $order->receiver_phone }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value">{{ $order->receiver_email ?? '—' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Địa chỉ</div>
                    <div class="info-value">{{ $order->shipping_address }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tỉnh/TP</div>
                    <div class="info-value">ID: {{ $order->shipping_province_id }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Quận/Huyện</div>
                    <div class="info-value">ID: {{ $order->shipping_district_id }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phường/Xã</div>
                    <div class="info-value">ID: {{ $order->shipping_ward_id }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Thanh toán & Vận chuyển</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Phương thức</div>
                    <div class="info-value">
                        @if($order->payment_method === 'cod') COD
                        @elseif($order->payment_method === 'bank_transfer') Chuyển khoản
                        @elseif($order->payment_method === 'qr') QR Code
                        @elseif($order->payment_method === 'momo') MoMo
                        @elseif($order->payment_method === 'zalopay') ZaloPay
                        @elseif($order->payment_method === 'payos') PayOS
                        @else {{ $order->payment_method }}
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Mã giao dịch</div>
                    <div class="info-value">{{ $order->transaction_code ?? '—' }}</div>
                </div>
                @if($order->shipping_tracking_code)
                <div class="info-item">
                    <div class="info-label">Mã vận đơn GHN</div>
                    <div class="info-value">
                        <strong style="color:#1d4ed8;">{{ $order->shipping_tracking_code }}</strong>
                        @php
                            $ghnData = $order->shipping_raw_response['ghn'] ?? [];
                        @endphp
                        @if(!empty($ghnData['sort_code']))
                            <br><small style="color:#64748b;">Sort code: {{ $ghnData['sort_code'] }}</small>
                        @endif
                        @if(!empty($ghnData['expected_delivery_time']))
                            <br><small style="color:#15803d;">Dự kiến giao: {{ \Carbon\Carbon::parse($ghnData['expected_delivery_time'])->format('d/m/Y H:i') }}</small>
                        @endif
                        @if(!empty($ghnData['total_fee']))
                            <br><small style="color:#92400e;">Phí GHN: {{ !empty($ghnData['total_fee']) && is_numeric($ghnData['total_fee']) ? number_format((int) $ghnData['total_fee']) . ' đ' : 'N/A' }}</small>
                        @endif
                    </div>
                </div>
                @endif
                <div class="info-item">
                    <div class="info-label">Đơn vị vận chuyển</div>
                    <div class="info-value">{{ ucfirst($order->shipping_partner) }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Mã vận đơn</div>
                    <div class="info-value">{{ $order->shipping_tracking_code ?? '—' }}</div>
                </div>
            </div>
        </div>

        @if ($order->shipping_partner === 'ghn' && $order->shipping_tracking_code && $order->status !== 'cancelled' && $order->delivery_status !== 'cancelled')
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                    <h3>Trạng thái giao hàng (GHN)</h3>
                    <div style="display:flex;gap:8px;align-items:center;">
                        @if($order->shipping_tracking_code)
                            <span style="font-size:13px;color:#1d4ed8;background:#dbeafe;padding:4px 10px;border-radius:999px;">
                                Mã vận đơn: {{ $order->shipping_tracking_code }}
                            </span>
                            <form action="{{ route('admin.orders.sync-ghn', $order) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary" style="font-size:13px;">🔄 Đồng bộ GHN</button>
                            </form>
                            <a href="{{ route('admin.orders.edit-ghn', $order) }}" class="btn btn-outline-secondary" style="font-size:13px;">✏️ Sửa vận đơn</a>
                            <a href="{{ route('admin.orders.print-ghn', $order) }}" class="btn btn-outline-info" style="font-size:13px;" target="_blank">🖨️ In đơn hàng</a>
                            <button type="button" onclick="showGhnTicketModal()" class="btn btn-outline-warning" style="font-size:13px;">🎫 Tạo ticket</button>
                        @endif
                    </div>
                </div>

                @php
                    // Chỉ hiển thị tickets có trong danh sách từ GHN
                    $ghnTicketIds = collect($ticketsFromGhn ?? [])->pluck('id')->toArray();
                    $allTickets = collect($order->shipping_raw_response['tickets'] ?? []);
                    
                    // Filter: chỉ lấy tickets có id trong danh sách GHN
                    $tickets = $allTickets->filter(function($ticket) use ($ghnTicketIds) {
                        $ticketId = $ticket['id'] ?? null;
                        return $ticketId && in_array($ticketId, $ghnTicketIds);
                    })->sortByDesc('created_at')->values();
                @endphp

                @if($tickets->isNotEmpty())
                <div style="margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <h4 style="margin:0;font-size:14px;color:#0f172a;">Danh sách tickets hỗ trợ ({{ $tickets->count() }})</h4>
                        <form action="{{ route('admin.orders.sync-ghn-ticket-list', $order) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary" style="font-size:11px;padding:4px 8px;" title="Đồng bộ danh sách tickets từ GHN">🔄 Đồng bộ danh sách</button>
                        </form>
                    </div>
                    <div style="max-height:300px;overflow:auto;border:1px solid #eef2f7;border-radius:8px;padding:10px;">
                        @foreach($tickets as $ticket)
                            <div style="border-left:3px solid #f59e0b;padding-left:8px;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #eef2f7;">
                                <div style="display:flex;justify-content:space-between;align-items:start;">
                                    <div style="flex:1;">
                                        <div style="font-weight:600;color:#0f172a;display:flex;align-items:center;gap:8px;">
                                            <span>Ticket #{{ $ticket['id'] ?? 'N/A' }} - {{ $ticket['category'] ?? $ticket['type'] ?? 'N/A' }}</span>
                                            <form action="{{ route('admin.orders.sync-ghn-ticket', $order) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="ticket_id" value="{{ $ticket['id'] ?? '' }}">
                                                <button type="submit" class="btn btn-sm btn-outline-primary" style="font-size:10px;padding:2px 6px;" title="Đồng bộ từ GHN">🔄</button>
                                            </form>
                                        </div>
                                        <div style="font-size:12px;color:#475569;margin-top:4px;">
                                            Tạo: {{ \Carbon\Carbon::parse($ticket['created_at'] ?? now())->format('d/m/Y H:i') }}
                                            @if(!empty($ticket['created_by']))
                                                • {{ $ticket['created_by'] }}
                                            @endif
                                            @if(!empty($ticket['updated_at']) && $ticket['updated_at'] !== $ticket['created_at'])
                                                <br>Cập nhật: {{ \Carbon\Carbon::parse($ticket['updated_at'])->format('d/m/Y H:i') }}
                                            @endif
                                        </div>
                                        <div style="font-size:12px;color:#64748b;margin-top:4px;">
                                            {{ Str::limit($ticket['description'] ?? '', 150) }}
                                        </div>
                                        @if(!empty($ticket['conversations']) && is_array($ticket['conversations']) && count($ticket['conversations']) > 0)
                                            <div style="font-size:11px;color:#1d4ed8;margin-top:4px;">
                                                💬 {{ count($ticket['conversations']) }} tin nhắn
                                            </div>
                                        @endif
                                        <form action="{{ route('admin.orders.reply-ghn-ticket', $order) }}" method="POST" enctype="multipart/form-data" style="margin-top:4px;">
                                            @csrf
                                            <input type="hidden" name="ticket_id" value="{{ $ticket['id'] ?? '' }}">
                                            <div style="display:flex;gap:4px;align-items:start;">
                                                <textarea name="description" rows="2" class="form-control" required maxlength="2000" placeholder="Phản hồi..." style="font-size:11px;flex:1;"></textarea>
                                                <button type="submit" class="btn btn-sm btn-warning" style="font-size:10px;padding:4px 8px;white-space:nowrap;" title="Gửi phản hồi">📤</button>
                                            </div>
                                            <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.xlsx,.xls,.csv" style="font-size:10px;margin-top:4px;">
                                        </form>
                                        @if(!empty($ticket['attachments']) && is_array($ticket['attachments']) && count($ticket['attachments']) > 0)
                                            <div style="font-size:11px;color:#15803d;margin-top:4px;">
                                                📎 {{ count($ticket['attachments']) }} file đính kèm
                                            </div>
                                        @endif
                                    </div>
                                    <div style="display:flex;flex-direction:column;gap:4px;align-items:end;">
                                        <span class="badge badge-processing" style="font-size:11px;">
                                            {{ $ticket['status'] ?? 'Đang xử lý' }}
                                        </span>
                                        <form action="{{ route('admin.orders.get-ghn-ticket', $order) }}" method="GET" style="display:inline;">
                                            <input type="hidden" name="ticket_id" value="{{ $ticket['id'] ?? '' }}">
                                            <button type="submit" class="btn btn-sm btn-outline-info" style="font-size:10px;padding:2px 6px;" title="Xem chi tiết">👁️</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @else
                    <div style="margin-bottom:16px;padding:12px;background:#f8fafc;border-radius:8px;border:1px solid #eef2f7;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <div style="color:#64748b;font-size:13px;">
                                Chưa có tickets nào từ GHN cho đơn hàng này.
                            </div>
                            <form action="{{ route('admin.orders.sync-ghn-ticket-list', $order) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary" style="font-size:11px;padding:4px 8px;">🔄 Đồng bộ từ GHN</button>
                            </form>
                        </div>
                    </div>
                @endif

                @if(session('ticket_detail'))
                    @php
                        $ticketDetail = session('ticket_detail');
                    @endphp
                    <div style="margin-bottom:16px;padding:16px;background:#fef3c7;border-radius:8px;border:1px solid #f59e0b;">
                        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px;">
                            <h4 style="margin:0;font-size:14px;color:#0f172a;">Chi tiết Ticket #{{ $ticketDetail['id'] ?? 'N/A' }}</h4>
                            <button type="button" onclick="this.parentElement.parentElement.style.display='none';" style="background:none;border:none;color:#64748b;cursor:pointer;font-size:18px;">×</button>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;font-size:13px;margin-bottom:12px;">
                            <div>
                                <span style="color:#64748b;font-weight:600;">Loại:</span>
                                <span style="color:#0f172a;">{{ $ticketDetail['type'] ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span style="color:#64748b;font-weight:600;">Trạng thái:</span>
                                <span style="color:#0f172a;font-weight:600;">{{ $ticketDetail['status'] ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span style="color:#64748b;font-weight:600;">Mã đơn GHN:</span>
                                <span style="color:#0f172a;">{{ $ticketDetail['order_code'] ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span style="color:#64748b;font-weight:600;">Tạo lúc:</span>
                                <span style="color:#0f172a;">{{ !empty($ticketDetail['created_at']) ? \Carbon\Carbon::parse($ticketDetail['created_at'])->format('d/m/Y H:i') : 'N/A' }}</span>
                            </div>
                            @if(!empty($ticketDetail['updated_at']))
                            <div>
                                <span style="color:#64748b;font-weight:600;">Cập nhật:</span>
                                <span style="color:#0f172a;">{{ \Carbon\Carbon::parse($ticketDetail['updated_at'])->format('d/m/Y H:i') }}</span>
                            </div>
                            @endif
                            @if(!empty($ticketDetail['c_email']))
                            <div>
                                <span style="color:#64748b;font-weight:600;">Email:</span>
                                <span style="color:#0f172a;">{{ $ticketDetail['c_email'] }}</span>
                            </div>
                            @endif
                        </div>
                        <div style="margin-bottom:12px;">
                            <div style="color:#64748b;font-weight:600;font-size:13px;margin-bottom:4px;">Mô tả:</div>
                            <div style="color:#0f172a;font-size:13px;padding:8px;background:#fff;border-radius:4px;">{{ $ticketDetail['description'] ?? 'N/A' }}</div>
                        </div>
                        @if(!empty($ticketDetail['conversations']) && is_array($ticketDetail['conversations']) && count($ticketDetail['conversations']) > 0)
                            <div style="margin-bottom:12px;">
                                <div style="color:#64748b;font-weight:600;font-size:13px;margin-bottom:4px;">💬 Cuộc hội thoại ({{ count($ticketDetail['conversations']) }}):</div>
                                <div style="max-height:150px;overflow:auto;padding:8px;background:#fff;border-radius:4px;">
                                    @foreach($ticketDetail['conversations'] as $conv)
                                        <div style="padding:6px;margin-bottom:6px;border-left:2px solid #1d4ed8;padding-left:8px;font-size:12px;">
                                            <div style="color:#0f172a;font-weight:600;">{{ $conv['sender'] ?? ($conv['from_email'] ?? 'GHN') }}</div>
                                            <div style="color:#475569;">{{ $conv['message'] ?? $conv['content'] ?? $conv['body'] ?? '' }}</div>
                                            @if(!empty($conv['created_at']))
                                                <div style="color:#94a3b8;font-size:11px;margin-top:2px;">{{ \Carbon\Carbon::parse($conv['created_at'])->format('d/m/Y H:i') }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        <!-- Form phản hồi ticket -->
                        <div style="margin-top:12px;padding:12px;background:#fff;border-radius:4px;border:1px solid #eef2f7;">
                            <h5 style="margin:0 0 8px;font-size:13px;color:#0f172a;">Phản hồi ticket</h5>
                            <form action="{{ route('admin.orders.reply-ghn-ticket', $order) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="ticket_id" value="{{ $ticketDetail['id'] ?? '' }}">
                                <div style="display:flex;flex-direction:column;gap:8px;">
                                    <div>
                                        <textarea name="description" rows="3" class="form-control" required maxlength="2000" placeholder="Nhập nội dung phản hồi...">{{ old('description') }}</textarea>
                                        @error('description')
                                            <span class="text-danger" style="font-size:11px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.xlsx,.xls,.csv" style="font-size:12px;">
                                        <small style="color:#64748b;font-size:10px;">File đính kèm (tùy chọn): jpg, jpeg, png, gif, pdf, xlsx, xls, csv (tối đa 10MB)</small>
                                        @error('attachment')
                                            <span class="text-danger" style="font-size:11px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-warning" style="font-size:12px;padding:6px 12px;">📤 Gửi phản hồi</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        @if(!empty($ticketDetail['attachments']) && is_array($ticketDetail['attachments']) && count($ticketDetail['attachments']) > 0)
                            <div>
                                <div style="color:#64748b;font-weight:600;font-size:13px;margin-bottom:4px;">📎 File đính kèm ({{ count($ticketDetail['attachments']) }}):</div>
                                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                    @foreach($ticketDetail['attachments'] as $attachment)
                                        <div style="padding:6px;background:#fff;border-radius:4px;font-size:12px;">
                                            {{ $attachment['name'] ?? $attachment['filename'] ?? 'File' }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Modal tạo ticket GHN -->
                <div id="ghn-ticket-modal" class="ghn-modal-overlay" aria-hidden="true" role="dialog">
                    <div class="ghn-modal">
                        <div class="ghn-modal-header">
                            <h4>🎫 Tạo ticket hỗ trợ GHN</h4>
                            <button type="button" class="ghn-modal-close" aria-label="Đóng" onclick="hideGhnTicketModal()">×</button>
                        </div>
                        <div class="ghn-modal-body">
                            <form action="{{ route('admin.orders.create-ghn-ticket', $order) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div style="display:flex;flex-direction:column;gap:12px;">
                                    <div>
                                        <label for="ticket-category" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Loại ticket <span style="color:red;">*</span></label>
                                        <select name="category" id="ticket-category" class="form-control" required>
                                            <option value="">-- Chọn loại ticket --</option>
                                            <option value="Tư vấn" {{ old('category') === 'Tư vấn' ? 'selected' : '' }}>Tư vấn</option>
                                            <option value="Hối Giao/Lấy/Trả hàng" {{ old('category') === 'Hối Giao/Lấy/Trả hàng' ? 'selected' : '' }}>Hối Giao/Lấy/Trả hàng</option>
                                            <option value="Thay đổi thông tin" {{ old('category') === 'Thay đổi thông tin' ? 'selected' : '' }}>Thay đổi thông tin</option>
                                            <option value="Khiếu nại" {{ old('category') === 'Khiếu nại' ? 'selected' : '' }}>Khiếu nại</option>
                                        </select>
                                        @error('category')
                                            <span class="text-danger" style="font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="ticket-description" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Mô tả yêu cầu <span style="color:red;">*</span></label>
                                        <textarea name="description" id="ticket-description" rows="4" class="form-control" required maxlength="2000" placeholder="Mô tả rõ yêu cầu để GHN hỗ trợ vấn đề (tối đa 2000 ký tự)">{{ old('description') }}</textarea>
                                        <small style="color:#64748b;font-size:11px;">Còn <span id="description-count">2000</span> ký tự</small>
                                        @error('description')
                                            <span class="text-danger" style="font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="ticket-email" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">Email liên hệ</label>
                                        <input type="email" name="c_email" id="ticket-email" class="form-control" value="{{ old('c_email', '') }}" placeholder="cskh@ghn.vn">
                                        @error('c_email')
                                            <span class="text-danger" style="font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="ticket-attachment" style="font-size:13px;font-weight:600;color:#475569;display:block;margin-bottom:4px;">File đính kèm (tùy chọn)</label>
                                        <input type="file" name="attachment" id="ticket-attachment" class="form-control" accept=".jpg,.jpeg,.png,.gif,.pdf,.xlsx,.xls,.csv">
                                        <small style="color:#64748b;font-size:11px;">Chấp nhận: jpg, jpeg, png, gif, pdf, xlsx, xls, csv (tối đa 10MB)</small>
                                        @error('attachment')
                                            <span class="text-danger" style="font-size:12px;">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="ghn-modal-actions">
                                        <button type="submit" class="btn btn-warning" style="font-size:13px;">🎫 Tạo ticket</button>
                                        <button type="button" class="btn btn-secondary" style="font-size:13px;" onclick="hideGhnTicketModal()">Đóng</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    @if($currentShippingStatus)
                        <div style="font-weight:600;color:#0f172a;">
                            Trạng thái hiện tại:
                            <span class="badge badge-processing" style="margin-left:6px;">{{ $currentShippingStatus['label'] ?? strtoupper($currentShippingStatus['status']) }}</span>
                        </div>
                        <div style="color:#475569;font-size:13px;margin-top:4px;">
                            {{ $currentShippingStatus['description'] ?? '' }}
                        </div>
                    @else
                        <div style="color:#94a3b8;">Chưa có trạng thái giao hàng nào.</div>
                    @endif
                    
                    @if(!empty($ghnData))
                        <div style="margin-top:12px;padding:12px;background:#f8fafc;border-radius:8px;border-left:3px solid #1d4ed8;">
                            {{-- Hiển thị các link in đơn hàng nếu có --}}
                            @if(session('ghn_print_urls'))
                                @php
                                    $printUrls = session('ghn_print_urls');
                                @endphp
                                <div style="margin-bottom:12px;padding:10px;background:#fff;border-radius:6px;border:1px solid #eef2f7;">
                                    <div style="font-weight:600;color:#0f172a;font-size:13px;margin-bottom:8px;">🖨️ In đơn hàng GHN (Token có hiệu lực 30 phút):</div>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                        <a href="{{ $printUrls['a5'] ?? '#' }}" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size:12px;padding:4px 12px;">📄 In A5</a>
                                        <a href="{{ $printUrls['80x80'] ?? '#' }}" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size:12px;padding:4px 12px;">📄 In 80x80</a>
                                        <a href="{{ $printUrls['52x70'] ?? '#' }}" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size:12px;padding:4px 12px;">📄 In 52x70</a>
                                    </div>
                                </div>
                            @endif
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;font-size:13px;">
                                @if(!empty($ghnData['expected_delivery_time']))
                                    <div>
                                        <span style="color:#64748b;font-weight:600;">Dự kiến giao:</span>
                                        <span style="color:#0f172a;">{{ \Carbon\Carbon::parse($ghnData['expected_delivery_time'])->format('d/m/Y H:i') }}</span>
                                    </div>
                                @endif
                                @if(!empty($ghnData['total_fee']) && is_numeric($ghnData['total_fee']))
                                    <div>
                                        <span style="color:#64748b;font-weight:600;">Phí vận chuyển GHN:</span>
                                        <span style="color:#0f172a;font-weight:600;">{{ number_format((int) $ghnData['total_fee']) }} đ</span>
                                    </div>
                                @endif
                                @if(!empty($ghnData['sort_code']))
                                    <div>
                                        <span style="color:#64748b;font-weight:600;">Sort code:</span>
                                        <span style="color:#0f172a;">{{ $ghnData['sort_code'] }}</span>
                                    </div>
                                @endif
                                @if(!empty($ghnData['fee']) && is_array($ghnData['fee']))
                                    @if(isset($ghnData['fee']['main_service']))
                                        <div>
                                            <span style="color:#64748b;font-weight:600;">Phí dịch vụ chính:</span>
                                            <span style="color:#0f172a;">{{ number_format((int) ($ghnData['fee']['main_service'] ?? 0)) }} đ</span>
                                        </div>
                                    @endif
                                @elseif(!empty($ghnData['fee']) && is_numeric($ghnData['fee']))
                                    <div>
                                        <span style="color:#64748b;font-weight:600;">Phí cơ bản:</span>
                                        <span style="color:#0f172a;">{{ number_format((int) $ghnData['fee']) }} đ</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;flex-wrap:wrap;">
                    <div>
                        <h4 style="margin:0 0 8px;font-size:14px;color:#0f172a;">Lịch sử cập nhật</h4>
                        <div style="max-height:260px;overflow:auto;border:1px solid #eef2f7;border-radius:8px;padding:10px;">
                            @forelse($shippingHistory as $log)
                                <div style="border-left:3px solid #1d4ed8;padding-left:8px;margin-bottom:12px;">
                                    <div style="font-weight:600;color:#0f172a;">{{ $log['label'] ?? strtoupper($log['status'] ?? '') }}</div>
                                    <div style="font-size:12px;color:#475569;">
                                        {{ \Carbon\Carbon::parse($log['created_at'] ?? now())->format('d/m/Y H:i') }}
                                        @if(!empty($log['created_by']))
                                            • {{ $log['created_by'] }}
                                        @endif
                                    </div>
                                    @if(!empty($log['description']))
                                        <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $log['description'] }}</div>
                                    @endif
                                    @if(!empty($log['note']))
                                        <div style="font-size:12px;color:#0f172a;margin-top:4px;"><strong>Ghi chú:</strong> {{ $log['note'] }}</div>
                                    @endif
                                </div>
                            @empty
                                <p style="margin:0;color:#94a3b8;font-size:13px;">Chưa có log trạng thái.</p>
                            @endforelse
                        </div>
                    </div>

                     <div>
                        <h4 style="margin:0 0 8px;font-size:14px;color:#0f172a;">Cập nhật trạng thái</h4>
                        <form action="{{ route('admin.orders.shipping-status.store', $order) }}" method="POST">
                            @csrf
                            <div style="display:flex;flex-direction:column;gap:10px;">
                                <div>
                                    <label for="shipping-status-select" style="font-size:13px;font-weight:600;color:#475569;">Trạng thái GHN</label>
                                    <select name="status" id="shipping-status-select" class="form-control" required>
                                        <option value="">-- Chọn trạng thái --</option>
                                        @foreach($shippingStatuses as $key => $status)
                                            <option value="{{ $key }}">{{ $status['label'] }} ({{ $key }})</option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <span class="text-danger" style="font-size:12px;">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label for="shipping-status-note" style="font-size:13px;font-weight:600;color:#475569;">Ghi chú</label>
                                    <textarea name="note" id="shipping-status-note" rows="3" class="form-control" placeholder="Ví dụ: GHN báo đã giao thành công, khách xác nhận...">{{ old('note') }}</textarea>
                                    @error('note')
                                        <span class="text-danger" style="font-size:12px;">{{ $message }}</span>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary" style="align-self:flex-start;">📦 Thêm trạng thái</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <h3>Sản phẩm trong đơn ({{ $order->items->count() }})</h3>
            <div class="table-responsive">
                <table class="items-table">
                    <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Sản phẩm</th>
                        <th>Biến thể</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($order->items as $item)
                        <tr>
                            <td>
                                @php
                                    $imageUrl = $item->variant?->primaryVariantImage
                                        ? asset('clients/assets/img/clothes/' . $item->variant->primaryVariantImage->url)
                                        : ($item->product->primaryImage
                                            ? asset('clients/assets/img/clothes/' . $item->product->primaryImage->url)
                                            : asset('clients/assets/img/clothes/no-image.webp'));
                                @endphp
                                <img src="{{ $imageUrl }}" alt="" class="product-image">
                            </td>
                            <td>
                                <strong>{{ $item->product->name }}</strong><br>
                                <small style="color:#64748b;">SKU: {{ $item->product->sku }}</small>
                            </td>
                            <td>
                                @if($item->variant)
                                    @php
                                        $attrs = is_string($item->variant->attributes) 
                                            ? json_decode($item->variant->attributes, true) 
                                            : $item->variant->attributes;
                                    @endphp
                                    @foreach($attrs as $key => $value)
                                        <span style="font-size:11px;color:#64748b;">{{ ucfirst($key) }}: {{ $value }}</span><br>
                                    @endforeach
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>{{ number_format($item->quantity) }}</td>
                            <td>{{ number_format($item->price) }} đ</td>
                            <td><strong>{{ number_format($item->total_price) }} đ</strong></td>
                            <td>
                                @if(!in_array($order->status, ['completed', 'cancelled']))
                                    <div style="display:flex;gap:6px;">
                                        <a href="{{ route('admin.order-items.edit', $item) }}" class="btn btn-sm btn-primary">✏️</a>
                                        <form action="{{ route('admin.order-items.destroy', $item) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:20px;color:#64748b;">Không có sản phẩm</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h3>Tổng tiền</h3>
            <div>
                <div class="summary-item">
                    <span>Tổng tiền sản phẩm:</span>
                    <strong>{{ number_format($order->total_price) }} đ</strong>
                </div>
                <div class="summary-item">
                    <span>Phí vận chuyển:</span>
                    <span>{{ number_format($order->shipping_fee) }} đ</span>
                </div>
                <div class="summary-item">
                    <span>Thuế:</span>
                    <span>{{ number_format($order->tax) }} đ</span>
                </div>
                <div class="summary-item">
                    <span>Giảm giá:</span>
                    <span>-{{ number_format($order->discount) }} đ</span>
                </div>
                @if($order->voucher_discount > 0)
                <div class="summary-item">
                    <span>Giảm giá voucher ({{ $order->voucher_code ?? 'N/A' }}):</span>
                    <span>-{{ number_format($order->voucher_discount) }} đ</span>
                </div>
                @endif
                <div class="summary-item total">
                    <span>Thành tiền:</span>
                    <strong>{{ number_format($order->final_price) }} đ</strong>
                </div>
            </div>
        </div>

        @if($order->customer_note || $order->admin_note)
        <div class="card">
            <h3>Ghi chú</h3>
            @if($order->customer_note)
            <div style="margin-bottom:12px;">
                <div class="info-label">Ghi chú khách hàng:</div>
                <div style="padding:8px;background:#f8fafc;border-radius:6px;font-size:13px;">{{ $order->customer_note }}</div>
            </div>
            @endif
            @if($order->admin_note)
            <div>
                <div class="info-label">Ghi chú nội bộ:</div>
                <div style="padding:8px;background:#fef3c7;border-radius:6px;font-size:13px;">{{ $order->admin_note }}</div>
            </div>
            @endif
        </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    // Prevent undefined errors if button clicked before DOM ready
    window.showGhnModal = window.showGhnModal || function() {};
    window.hideGhnModal = window.hideGhnModal || function() {};
    window.showGhnTicketModal = window.showGhnTicketModal || function() {};
    window.hideGhnTicketModal = window.hideGhnTicketModal || function() {};

    document.addEventListener('DOMContentLoaded', function() {
        const ghnModal = document.getElementById('ghn-create-order-modal');
        const ghnTicketModal = document.getElementById('ghn-ticket-modal');
        const pickShiftSelect = document.getElementById('pick-shift-id');
        let shiftsLoaded = false;

        window.showGhnModal = function() {
            if (!ghnModal) return;
            ghnModal.classList.add('is-active');
            document.body.classList.add('ghn-modal-open');
            if (!shiftsLoaded) {
                loadPickShifts();
            }
        };

        window.hideGhnModal = function() {
            if (!ghnModal) return;
            ghnModal.classList.remove('is-active');
            document.body.classList.remove('ghn-modal-open');
        };

        window.showGhnTicketModal = function() {
            if (!ghnTicketModal) return;
            ghnTicketModal.classList.add('is-active');
            document.body.classList.add('ghn-modal-open');
        };

        window.hideGhnTicketModal = function() {
            if (!ghnTicketModal) return;
            ghnTicketModal.classList.remove('is-active');
            document.body.classList.remove('ghn-modal-open');
        };

        if (ghnModal) {
            ghnModal.addEventListener('click', function(event) {
                if (event.target === ghnModal) {
                    hideGhnModal();
                }
            });
        }

        if (ghnTicketModal) {
            ghnTicketModal.addEventListener('click', function(event) {
                if (event.target === ghnTicketModal) {
                    hideGhnTicketModal();
                }
            });
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (ghnModal && ghnModal.classList.contains('is-active')) {
                    hideGhnModal();
                }
                if (ghnTicketModal && ghnTicketModal.classList.contains('is-active')) {
                    hideGhnTicketModal();
                }
            }
        });

        function loadPickShifts() {
            if (!pickShiftSelect) return;

            pickShiftSelect.innerHTML = '<option value="">-- Đang tải... --</option>';
            pickShiftSelect.disabled = true;

            fetch('{{ route("admin.orders.get-pick-shifts") }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            })
            .then(response => response.json())
            .then(data => {
                pickShiftSelect.innerHTML = '<option value="">-- Chọn ca lấy hàng --</option>';
                
                if (data.success && data.shifts && data.shifts.length > 0) {
                    data.shifts.forEach(shift => {
                        const option = document.createElement('option');
                        option.value = shift.id;
                        option.textContent = shift.title || `Ca lấy ${shift.id}`;
                        pickShiftSelect.appendChild(option);
                    });
                } else {
                    pickShiftSelect.innerHTML = '<option value="">-- Không có ca lấy hàng --</option>';
                }
                pickShiftSelect.disabled = false;
                shiftsLoaded = true;
            })
            .catch(error => {
                console.error('Error loading pick shifts:', error);
                pickShiftSelect.innerHTML = '<option value="">-- Lỗi tải ca lấy hàng --</option>';
                pickShiftSelect.disabled = false;
                shiftsLoaded = false;
            });
        }

        // Character counter for ticket description
        const descriptionTextarea = document.getElementById('ticket-description');
        const descriptionCount = document.getElementById('description-count');
        
        if (descriptionTextarea && descriptionCount) {
            descriptionTextarea.addEventListener('input', function() {
                const remaining = 2000 - this.value.length;
                descriptionCount.textContent = remaining;
                if (remaining < 0) {
                    descriptionCount.style.color = 'red';
                } else if (remaining < 100) {
                    descriptionCount.style.color = '#f59e0b';
                } else {
                    descriptionCount.style.color = '#64748b';
                }
            });
        }
    });
</script>
@endpush

