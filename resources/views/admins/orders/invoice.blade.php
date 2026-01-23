<!DOCTYPE html>
<html lang="vi">
@php
    // Đảm bảo đường dẫn font hoạt động trong môi trường web và môi trường tạo PDF (nếu $isPrintable là false)
    $isPrintable = ($printMode ?? true) === true;
    // Cần đảm bảo rằng `asset('fonts/DejaVuSans.ttf')` hoặc `public_path('fonts/DejaVuSans.ttf')` trỏ đúng đến file font
    $fontSource = $isPrintable ? asset('fonts/DejaVuSans.ttf') : public_path('fonts/DejaVuSans.ttf');
@endphp
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn {{ $order->code }}</title>
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/invoice-icon.png') }}" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        @font-face {
            font-family: 'InvoiceFont';
            src: url('{{ $fontSource }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: 'InvoiceFont', DejaVu Sans, sans-serif;
            margin: 0;
            padding: 15px; /* Giảm padding tổng thể */
            background: #f8f9fa;
            color: #343a40;
            font-size: 13px; /* Giảm font cơ bản */
            
        }

        .invoice {
            position: relative;
            max-width: 780px; /* Chiều rộng nhỏ hơn */
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #dee2e6;
            padding: 25px; /* Giảm padding bên trong */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        /* Watermark ĐÃ THANH TOÁN - Giữ nguyên kích thước lớn */
        .paid-watermark {
            position: absolute;
            top: 15%;
            right: -20%;
            transform: rotate(40deg);
            font-size: 75px;
            font-weight: 900;
            color: rgba(34, 197, 94, 0.282);
            white-space: nowrap;
            pointer-events: none;
            user-select: none;
            z-index: 10;
        }

        .logo-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 75px;
            font-weight: 900;
            color: rgba(34,197,94,0.1);
            white-space: nowrap;
            pointer-events: none;
            user-select: none;
            z-index: 10;
            opacity: .05;
        }

        .logo-watermark img {
            width: 250px;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px; /* Giảm margin bottom */
            padding-bottom: 12px; /* Giảm padding bottom */
            border-bottom: 2px solid #ff3366;
        }

        .invoice-title {
            font-size: 26px; /* Giảm kích thước tiêu đề */
            font-weight: bold;
            color: #ff3366;
            margin-bottom: 3px;
        }

        .company-info {
            width: 55%;
            height: auto;
            text-align: right;
            font-size: 12px; /* Giảm font info cty */
            line-height: 1.5;
        }
        .company-info strong {
            font-size: 15px;
            color: #212529;
        }

        .invoice-header-left {
            width: 40%;
        }

        .meta-group {
            font-size: 13px; /* Font cho thông tin hóa đơn */
            color: #6c757d;
            line-height: 1.5; /* Giảm line height */
        }

        .section {
            margin-bottom: 18px; /* Giảm margin section */
        }

        .section h3 {
            margin: 0 0 8px; /* Giảm margin title section */
            font-size: 15px;
            color: #495057;
            padding-bottom: 4px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }

        .grid {
            display: flex;
            gap: 15px; /* Giảm gap */
            margin-bottom: 20px; /* Giảm margin bottom */
        }

        .grid > div {
            flex: 1;
            padding: 12px; /* Giảm padding box info */
            border-radius: 5px;
            border: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        .meta-item {
            padding: 2px 0; /* Giảm padding item meta */
        }
        .meta-item strong {
            color: #212529;
            font-size: 13px;
        }

        /* Chi tiết sản phẩm */
        table {
            width: 100%;
            margin-top: 0;
        }

        table th {
            font-size: 12px; /* Giảm font header table */
        }

        table td {
            font-size: 13px; /* Giảm font cell table */
        }

        .align-right {
            text-align: right !important;
        }

        /* --- Tối ưu CSS cho .summary --- */
        .summary {
            width: 100%; /* Giảm width tối đa để bé lại */
            margin-left: auto; /* Căn phải hoàn toàn */
            margin-top: 5px; /* Giảm khoảng cách trên */
            padding: 12px; /* Giảm padding bên trong */
            border-radius: 6px; /* Bo góc nhẹ */
            background: #ffffff; /* Nền trắng sạch */
            border: 1px solid #ced4da; /* Viền xám nhạt */
            box-shadow: 0 1px 3px rgba(0,0,0,0.05); /* Shadow nhẹ hơn */
            font-size: 13px; /* Font tổng thể nhỏ hơn */
        }

        .summary div {
            display: flex;
            justify-content: space-between;
            padding: 3px 0; /* Giảm khoảng cách giữa các dòng */
            /* Không cần font-size ở đây nữa, dùng font-size tổng thể */
        }

        .summary span {
            color: #495057; /* Màu chữ tiêu đề nhạt hơn */
        }

        .summary strong {
            color: #212529; /* Màu chữ giá trị đậm hơn */
            font-weight: 600;
        }

        .summary .total {
            font-size: 16px; /* Kích thước font Total */
            font-weight: 700;
            border-top: 2px solid #ff3366; /* Viền xanh nổi bật */
            margin-top: 8px;
            padding-top: 8px;
            color: #ff3366; /* Màu tổng tiền nổi bật */
        }
        /* --- Kết thúc Tối ưu CSS cho .summary --- */

        .badge {
            padding: 5px 6px; /* Giảm padding badge */
            font-size: 11px; /* Giảm font badge */
        }

        .print-actions {
            margin-bottom: 15px;
        }

        .print-actions a {
            padding: 8px 15px;
            font-size: 13px;
        }

        .notes {
            margin-top: 20px; /* Giảm margin top notes */
            padding-top: 10px;
            font-size: 12px;
        }
        
        /* Đảm bảo chỉ in 1 trang */
        @media print {
            .print-actions { display: none; }
            body { padding: 0; background: #fff; -webkit-print-color-adjust: exact; }
            .invoice { border: none; box-shadow: none; max-width: 100%; padding: 20px; } /* Giảm padding khi in */
        }
    </style>
    
</head>
<body>
    @if(($printMode ?? true) === true)
        <div class="print-actions">
            <a href="#" onclick="window.print();return false;">🖨️ In hóa đơn</a>
            <a href="{{ route('admin.orders.invoice.pdf', $order) }}">⬇️ Tải PDF</a>
        </div>
    @endif
    <div class="invoice">
        <div class="logo-watermark">
            <img src="{{ asset('clients/assets/img/business/' . ($settings->site_favicon ?? '')) }}" alt="Logo" class="logo">
        </div>
        @if(strtoupper($order->payment_status) == 'PAID')
        <div class="paid-watermark">ĐÃ THANH TOÁN</div>
        @endif
        
        <div class="invoice-header">
            <div class="invoice-header-left">
                <div class="invoice-title">HÓA ĐƠN BÁN HÀNG</div>
                <div class="meta-group">
                    <div class="meta-item"><span>Số Hóa đơn:</span> <strong>{{ $invoiceNumber }}</strong></div>
                    <div class="meta-item"><span>Ngày lập:</span> {{ now()->format('d/m/Y H:i') }}</div>
                </div>
            </div>
            <div class="company-info">
                <strong>{{ $settings->site_name ?? 'NOBI FASHION VIỆT NAM' }}</strong><br>
                Địa chỉ: {{ $settings->contact_address ?? 'Ngõ 512 Thiên Lôi - Vĩnh Niệm - Lê Chân - Hải Phòng' }}<br>
                Email: {{ $settings->contact_email ?? 'support@nobifashion.vn' }}<br>
                Hotline: {{ $settings->contact_phone ?? '0827786198' }}
            </div>
        </div>

        <div class="section grid">
            <div>
                <h3>Thông tin khách hàng</h3>
                <div class="meta-group">
                    {{-- Thông tin Khách hàng được định dạng key: value --}}
                    <div class="meta-item"><span>Khách hàng:</span> <strong>Minh Đức Nguyễn</strong></div>
                    <div class="meta-item"><span>Điện thoại:</span> 0827786198</div>
                    <div class="meta-item"><span>Email:</span> nguyenminhduc552004@gmail.com</div>
                    {{-- Gộp Địa chỉ thành một dòng dài hơn nếu cần --}}
                    <div class="meta-item"><span>Địa chỉ:</span> Hải Phòng, Việt Nam</div> 
                </div>
            </div>
            <div>
                <h3>Thông tin đơn hàng</h3>
                <div class="meta-group">
                    {{-- Thông tin Đơn hàng được định dạng key: value --}}
                    <div class="meta-item"><span>Mã đơn hàng:</span> <strong>{{ $order->code }}</strong></div>
                    <div class="meta-item"><span>Thanh toán:</span> <span style="text-transform: uppercase;">{{ $order->payment_method }} / {{ $order->payment_status }}</span></div>
                    <div class="meta-item"><span>Vận chuyển:</span> <span style="text-transform: uppercase;">{{ $order->shipping_partner }}</span></div>
                    {{-- Dòng trạng thái giao hàng --}}
                    <div class="meta-item">
                        <span>Trạng thái giao:</span> 
                        <span class="badge" style="background:#d4edda; color:#259d41; border-color:#c3e6cb;">DELIVERED</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Chi tiết sản phẩm</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 40%;">Sản phẩm</th>
                        <th style="width: 20%;">Mã sản phẩm</th>
                        <th class="align-right" style="width: 10%;">Số lượng</th>
                        <th class="align-right" style="width: 15%;">Đơn giá</th>
                        <th class="align-right" style="width: 15%;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->product->name ?? 'Sản phẩm' }}</td>
                            <td>{{ $item->variant?->sku ?? $item->product?->sku }}</td>
                            <td class="align-right">{{ $item->quantity }}</td>
                            <td class="align-right">{{ number_format($item->price, 0, ',', '.') }} đ</td>
                            <td class="align-right"><strong>{{ number_format($item->price * $item->quantity, 0, ',', '.') }} đ</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="summary">
            <div>
                <span>Tạm tính (Tổng SP)</span>
                <strong>{{ number_format($order->total_price, 0, ',', '.') }} đ</strong>
            </div>
            <div>
                <span>Phí vận chuyển</span>
                <strong>+{{ number_format($order->shipping_fee, 0, ',', '.') }} đ</strong>
            </div>
            <div>
                <span>Thuế</span>
                <strong>+{{ number_format($order->tax, 0, ',', '.') }} đ</strong>
            </div>
            <div>
                <span>Giảm giá</span>
                <strong>-{{ number_format($order->discount, 0, ',', '.') }} đ</strong>
            </div>
            <div>
                <span>Voucher</span>
                <strong>-{{ number_format($order->voucher_discount, 0, ',', '.') }} đ</strong>
            </div>
            <div class="total">
                <span>TỔNG THANH TOÁN</span>
                <strong>{{ number_format($order->final_price, 0, ',', '.') }} đ</strong>
            </div>
        </div>

        <div class="notes meta-group">
            <strong>Ghi chú:</strong><br>
            {{ $order->customer_note ?? '---' }}
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>