@extends('clients.layouts.master')

@section('title', 'Sổ địa chỉ giao hàng | ' . renderMeta($settings->site_name ?? ($settings->subname ?? 'NOBI FASHION VIỆT NAM')))

@section('head')
    <meta name="robots" content="follow, noindex"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --nobi-prof-bg: #f8fafc;
            --nobi-prof-card: #ffffff;
            --nobi-prof-border: #e2e8f0;
            --nobi-prof-border-light: #f1f5f9;
            --nobi-prof-primary: #0f172a;
            --nobi-prof-primary-hover: #1e293b;
            --nobi-prof-accent: #2563eb;
            --nobi-prof-accent-hover: #1d4ed8;
            --nobi-prof-accent-soft: #eff6ff;
            --nobi-prof-danger: #ef4444;
            --nobi-prof-danger-hover: #dc2626;
            --nobi-prof-danger-soft: #fef2f2;
            --nobi-prof-success: #10b981;
            --nobi-prof-success-soft: #ecfdf5;
            --nobi-prof-text-main: #0f172a;
            --nobi-prof-text-muted: #64748b;
            --nobi-prof-radius: 16px;
            --nobi-prof-radius-sm: 8px;
            --nobi-prof-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.04);
            --nobi-prof-shadow-hover: 0 10px 25px -3px rgba(15, 23, 42, 0.1);
            --nobi-prof-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .nobi_addr_wrapper {
            background-color: var(--nobi-prof-bg);
            min-height: calc(100vh - 120px);
            padding: 24px 16px 64px;
            font-family: var(--nobi-prof-font);
        }

        .nobi_addr_container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Breadcrumb */
        .nobi_addr_breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            color: var(--nobi-prof-text-muted);
            margin-bottom: 20px;
        }

        .nobi_addr_breadcrumb a {
            color: var(--nobi-prof-text-muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .nobi_addr_breadcrumb a:hover {
            color: var(--nobi-prof-accent);
        }

        .nobi_addr_breadcrumb .separator {
            font-size: 11px;
            color: #94a3b8;
        }

        .nobi_addr_breadcrumb .current {
            color: var(--nobi-prof-text-main);
            font-weight: 600;
        }

        /* Top Header */
        .nobi_addr_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            background: var(--nobi-prof-card);
            border: 1px solid var(--nobi-prof-border);
            border-radius: var(--nobi-prof-radius);
            padding: 24px 28px;
            margin-bottom: 24px;
            box-shadow: var(--nobi-prof-shadow);
        }

        .nobi_addr_header_title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .nobi_addr_header_icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--nobi-prof-accent-soft);
            color: var(--nobi-prof-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .nobi_addr_header_text h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--nobi-prof-text-main);
            margin: 0 0 4px 0;
        }

        .nobi_addr_header_text p {
            margin: 0;
            font-size: 13.5px;
            color: var(--nobi-prof-text-muted);
        }

        .nobi_addr_header_actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Buttons */
        .nobi_btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
            border-radius: var(--nobi-prof-radius-sm);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            box-sizing: border-box;
            line-height: 1.4;
            border: 1px solid transparent;
        }

        .nobi_btn_primary {
            background: var(--nobi-prof-accent);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }

        .nobi_btn_primary:hover {
            background: var(--nobi-prof-accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
            color: #ffffff;
        }

        .nobi_btn_secondary {
            background: #ffffff;
            color: #334155;
            border-color: var(--nobi-prof-border);
        }

        .nobi_btn_secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: var(--nobi-prof-text-main);
        }

        .nobi_btn_sm {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
        }

        .nobi_btn_danger_ghost {
            background: transparent;
            color: var(--nobi-prof-danger);
            border: 1px solid transparent;
        }

        .nobi_btn_danger_ghost:hover {
            background: var(--nobi-prof-danger-soft);
            border-color: #fecaca;
        }

        .nobi_btn_default_ghost {
            background: transparent;
            color: var(--nobi-prof-accent);
            border: 1px solid #bfdbfe;
        }

        .nobi_btn_default_ghost:hover {
            background: var(--nobi-prof-accent-soft);
            border-color: var(--nobi-prof-accent);
        }

        /* Alert Messages */
        .nobi_alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: var(--nobi-prof-radius-sm);
            margin-bottom: 20px;
            font-size: 14px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .nobi_alert_success {
            background-color: var(--nobi-prof-success-soft);
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .nobi_alert_error {
            background-color: var(--nobi-prof-danger-soft);
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        /* Address Grid */
        .nobi_addr_grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }

        /* Address Card */
        .nobi_addr_card {
            background: var(--nobi-prof-card);
            border: 1px solid var(--nobi-prof-border);
            border-radius: var(--nobi-prof-radius);
            padding: 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: all 0.25s ease;
            box-shadow: var(--nobi-prof-shadow);
        }

        .nobi_addr_card:hover {
            transform: translateY(-2px);
            box-shadow: var(--nobi-prof-shadow-hover);
        }

        .nobi_addr_card.is-default {
            border-color: var(--nobi-prof-accent);
            background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
            box-shadow: 0 6px 20px -2px rgba(37, 99, 235, 0.12);
        }

        .nobi_addr_card.is-default::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--nobi-prof-accent);
            border-top-left-radius: var(--nobi-prof-radius);
            border-top-right-radius: var(--nobi-prof-radius);
        }

        /* Card Header */
        .nobi_addr_card_top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .nobi_addr_recipient_name {
            font-size: 16px;
            font-weight: 700;
            color: var(--nobi-prof-text-main);
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nobi_addr_phone {
            font-size: 13.5px;
            color: var(--nobi-prof-text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nobi_addr_badges {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .nobi_badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            font-size: 11.5px;
            font-weight: 600;
            border-radius: 999px;
            text-transform: capitalize;
            line-height: 1.2;
        }

        .nobi_badge_default {
            background-color: var(--nobi-prof-accent-soft);
            color: var(--nobi-prof-accent);
            border: 1px solid #bfdbfe;
        }

        .nobi_badge_home {
            background-color: #f1f5f9;
            color: #475569;
        }

        .nobi_badge_work {
            background-color: #fef3c7;
            color: #92400e;
        }

        /* Card Body */
        .nobi_addr_detail_box {
            font-size: 14px;
            line-height: 1.6;
            color: #334155;
            padding: 12px 0;
            border-top: 1px dashed var(--nobi-prof-border);
            border-bottom: 1px dashed var(--nobi-prof-border);
            margin: 12px 0 16px 0;
            flex: 1;
        }

        .nobi_addr_line {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 4px;
        }

        .nobi_addr_line i {
            color: var(--nobi-prof-accent);
            margin-top: 4px;
            font-size: 13px;
            flex-shrink: 0;
        }

        .nobi_addr_notes {
            margin-top: 8px;
            font-size: 12.5px;
            color: var(--nobi-prof-text-muted);
            background: #f8fafc;
            padding: 6px 10px;
            border-radius: 6px;
            border-left: 3px solid #cbd5e1;
        }

        /* Card Footer Actions */
        .nobi_addr_actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        .nobi_addr_actions_left {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nobi_addr_actions_right {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
        }

        /* Empty State */
        .nobi_addr_empty {
            background: var(--nobi-prof-card);
            border: 2px dashed #cbd5e1;
            border-radius: var(--nobi-prof-radius);
            padding: 48px 24px;
            text-align: center;
            margin: 20px 0;
        }

        .nobi_addr_empty_icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: var(--nobi-prof-accent-soft);
            color: var(--nobi-prof-accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .nobi_addr_empty h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--nobi-prof-text-main);
            margin: 0 0 8px 0;
        }

        .nobi_addr_empty p {
            color: var(--nobi-prof-text-muted);
            font-size: 14px;
            margin: 0 0 20px 0;
            max-width: 440px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Modal Backdrop & Popup */
        .nobi_modal_overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            animation: nobiOverlayFadeIn 0.2s ease-out forwards;
        }

        .nobi_modal_overlay.is-active {
            display: flex;
        }

        @keyframes nobiOverlayFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .nobi_modal_card {
            background: #ffffff;
            border-radius: var(--nobi-prof-radius);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 620px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: nobiModalZoomIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes nobiModalZoomIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .nobi_modal_header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--nobi-prof-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            flex-shrink: 0;
        }

        .nobi_modal_header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: var(--nobi-prof-text-main);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nobi_modal_close_btn {
            background: transparent;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .nobi_modal_close_btn:hover {
            background: #f1f5f9;
            color: var(--nobi-prof-text-main);
        }

        .nobi_modal_card form {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }

        .nobi_modal_body {
            padding: 20px 24px;
            overflow-y: auto;
            flex: 1;
            min-height: 0;
        }

        /* Form Controls */
        .nobi_form_grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .nobi_form_group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .nobi_form_group.full-width {
            grid-column: 1 / -1;
        }

        .nobi_form_group.three-cols {
            grid-column: span 1;
        }

        .nobi_form_label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nobi_form_label .req {
            color: var(--nobi-prof-danger);
        }

        .nobi_form_input,
        .nobi_form_select,
        .nobi_form_textarea {
            width: 100%;
            padding: 9px 13px;
            font-size: 14px;
            color: var(--nobi-prof-text-main);
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: var(--nobi-prof-radius-sm);
            box-sizing: border-box;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .nobi_form_input:focus,
        .nobi_form_select:focus,
        .nobi_form_textarea:focus {
            outline: none;
            border-color: var(--nobi-prof-accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .nobi_form_textarea {
            resize: vertical;
            min-height: 60px;
        }

        .nobi_form_check {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: #f8fafc;
            border: 1px solid var(--nobi-prof-border);
            border-radius: var(--nobi-prof-radius-sm);
            cursor: pointer;
            user-select: none;
        }

        .nobi_form_check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--nobi-prof-accent);
            cursor: pointer;
        }

        .nobi_form_check span {
            font-size: 13.5px;
            font-weight: 500;
            color: var(--nobi-prof-text-main);
        }

        .nobi_modal_footer {
            padding: 14px 24px;
            background: #f8fafc;
            border-top: 1px solid var(--nobi-prof-border);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-shrink: 0;
        }

        @media (max-width: 640px) {
            .nobi_form_grid {
                grid-template-columns: 1fr;
            }
            .nobi_form_group.three-cols {
                grid-column: 1 / -1;
            }
            .nobi_addr_header {
                padding: 18px;
            }
            .nobi_addr_header_title {
                width: 100%;
            }
            .nobi_addr_header_actions {
                width: 100%;
                justify-content: flex-start;
            }
            .nobi_addr_grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
<div class="nobi_addr_wrapper">
    <div class="nobi_addr_container">
        <!-- Breadcrumb -->
        <nav class="nobi_addr_breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('client.home.index') }}"><i class="fas fa-home"></i> Trang chủ</a>
            <span class="separator">/</span>
            <a href="{{ route('client.profile.index') }}">Tài khoản cá nhân</a>
            <span class="separator">/</span>
            <span class="current">Sổ địa chỉ</span>
        </nav>

        <!-- Top Header Card -->
        <div class="nobi_addr_header">
            <div class="nobi_addr_header_title">
                <div class="nobi_addr_header_icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div class="nobi_addr_header_text">
                    <h1>Sổ địa chỉ giao hàng</h1>
                    <p>Quản lý danh sách địa chỉ nhận hàng để thanh toán thuận tiện và nhanh chóng hơn.</p>
                </div>
            </div>
            <div class="nobi_addr_header_actions">
                <a href="{{ route('client.profile.index') }}" class="nobi_btn nobi_btn_secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại hồ sơ
                </a>
                <button type="button" class="nobi_btn nobi_btn_primary" onclick="openCreateAddressModal()">
                    <i class="fas fa-plus"></i> Thêm địa chỉ mới
                </button>
            </div>
        </div>

        <!-- Alert messages -->
        @if(session('success'))
            <div class="nobi_alert nobi_alert_success">
                <i class="fas fa-check-circle" style="font-size: 18px;"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="nobi_alert nobi_alert_error">
                <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i>
                <div>
                    <strong>Đã có lỗi xảy ra:</strong>
                    <ul style="margin: 4px 0 0 16px; padding: 0;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Address Grid List -->
        @if(count($addresses) > 0)
            <div class="nobi_addr_grid">
                @foreach($addresses as $address)
                    <div class="nobi_addr_card {{ $address->is_default ? 'is-default' : '' }}">
                        <div>
                            <div class="nobi_addr_card_top">
                                <div>
                                    <div class="nobi_addr_recipient_name">
                                        {{ $address->full_name }}
                                    </div>
                                    <div class="nobi_addr_phone">
                                        <i class="fas fa-phone-alt text-xs"></i>
                                        <span>{{ $address->phone_number }}</span>
                                    </div>
                                </div>
                                <div class="nobi_addr_badges">
                                    @if($address->is_default)
                                        <span class="nobi_badge nobi_badge_default">
                                            <i class="fas fa-check-circle"></i> Mặc định
                                        </span>
                                    @endif
                                    <span class="nobi_badge {{ $address->address_type === 'work' ? 'nobi_badge_work' : 'nobi_badge_home' }}">
                                        <i class="fas {{ $address->address_type === 'work' ? 'fa-briefcase' : 'fa-home' }}"></i>
                                        {{ $address->address_type === 'work' ? 'Văn phòng' : 'Nhà riêng' }}
                                    </span>
                                </div>
                            </div>

                            <div class="nobi_addr_detail_box">
                                <div class="nobi_addr_line">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <strong>{{ $address->detail_address }}</strong>
                                        <div>
                                            {{ $address->ward ? $address->ward . ', ' : '' }}{{ $address->district }}, {{ $address->province }}
                                        </div>
                                        <div style="font-size: 12.5px; color: #64748b; margin-top: 2px;">
                                            Mã bưu chính (Postal Code): {{ $address->postal_code ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                @if(!empty($address->notes))
                                    <div class="nobi_addr_notes">
                                        <i class="far fa-sticky-note"></i> Ghi chú: {{ $address->notes }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="nobi_addr_actions">
                            <div class="nobi_addr_actions_left">
                                @if(!$address->is_default)
                                    <form method="POST" action="{{ route('client.addresses.set-default', $address->id) }}">
                                        @csrf
                                        <button type="submit" class="nobi_btn nobi_btn_sm nobi_btn_default_ghost" title="Đặt làm địa chỉ nhận hàng mặc định">
                                            <i class="far fa-star"></i> Đặt mặc định
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <div class="nobi_addr_actions_right">
                                <button type="button" class="nobi_btn nobi_btn_sm nobi_btn_secondary" onclick='openEditAddressModal(@json($address))'>
                                    <i class="far fa-edit"></i> Sửa
                                </button>
                                <form method="POST" action="{{ route('client.addresses.destroy', $address->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn xóa địa chỉ này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="nobi_btn nobi_btn_sm nobi_btn_danger_ghost" title="Xóa địa chỉ">
                                        <i class="far fa-trash-alt"></i> Xóa
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty state -->
            <div class="nobi_addr_empty">
                <div class="nobi_addr_empty_icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3>Bạn chưa có địa chỉ nhận hàng nào</h3>
                <p>Thêm địa chỉ giao hàng ngay để hệ thống tự động điền nhanh chóng khi bạn đặt mua các sản phẩm yêu thích.</p>
                <button type="button" class="nobi_btn nobi_btn_primary" onclick="openCreateAddressModal()">
                    <i class="fas fa-plus"></i> Thêm địa chỉ mới ngay
                </button>
            </div>
        @endif
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: THÊM ĐỊA CHỈ MỚI -->
<!-- ========================================== -->
<div id="modalCreateAddress" class="nobi_modal_overlay" onclick="handleOverlayClick(event, 'modalCreateAddress')">
    <div class="nobi_modal_card">
        <div class="nobi_modal_header">
            <h3><i class="fas fa-map-pin text-primary"></i> Thêm địa chỉ nhận hàng mới</h3>
            <button type="button" class="nobi_modal_close_btn" onclick="closeModal('modalCreateAddress')" aria-label="Đóng">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('client.addresses.store') }}">
            @csrf
            <div class="nobi_modal_body">
                <div class="nobi_form_grid">
                    <div class="nobi_form_group">
                        <label class="nobi_form_label">Họ và tên người nhận <span class="req">*</span></label>
                        <input type="text" name="full_name" class="nobi_form_input" placeholder="Ví dụ: Nguyễn Văn A" required>
                    </div>

                    <div class="nobi_form_group">
                        <label class="nobi_form_label">Số điện thoại liên hệ <span class="req">*</span></label>
                        <input type="tel" name="phone_number" class="nobi_form_input" placeholder="Ví dụ: 0987654321" required pattern="(0|\+84)[0-9]{9,10}">
                    </div>

                    <div class="nobi_form_group full-width">
                        <label class="nobi_form_label">Địa chỉ chi tiết (Số nhà, tên ngõ/đường) <span class="req">*</span></label>
                        <input type="text" name="detail_address" class="nobi_form_input" placeholder="Ví dụ: Số 25 ngõ 102 Phố Vọng" required>
                    </div>

                    <div class="nobi_form_group three-cols">
                        <label class="nobi_form_label">Tỉnh / Thành phố <span class="req">*</span></label>
                        <input type="text" name="province" class="nobi_form_input" placeholder="Ví dụ: Hà Nội" required>
                    </div>

                    <div class="nobi_form_group three-cols">
                        <label class="nobi_form_label">Quận / Huyện <span class="req">*</span></label>
                        <input type="text" name="district" class="nobi_form_input" placeholder="Ví dụ: Hai Bà Trưng" required>
                    </div>

                    <div class="nobi_form_group three-cols">
                        <label class="nobi_form_label">Phường / Xã</label>
                        <input type="text" name="ward" class="nobi_form_input" placeholder="Ví dụ: Đồng Tâm">
                    </div>

                    <div class="nobi_form_group three-cols">
                        <label class="nobi_form_label">Mã bưu chính (Postal Code) <span class="req">*</span></label>
                        <input type="text" name="postal_code" class="nobi_form_input" placeholder="Ví dụ: 100000" value="100000" required>
                    </div>

                    <div class="nobi_form_group full-width">
                        <label class="nobi_form_label">Loại địa chỉ</label>
                        <select name="address_type" class="nobi_form_select">
                            <option value="home">Nhà riêng (Giao tất cả các ngày trong tuần)</option>
                            <option value="work">Văn phòng / Công ty (Giao giờ hành chính)</option>
                        </select>
                    </div>

                    <div class="nobi_form_group full-width">
                        <label class="nobi_form_label">Ghi chú giao hàng (Không bắt buộc)</label>
                        <textarea name="notes" class="nobi_form_textarea" placeholder="Ví dụ: Gọi trước khi giao 15 phút, gửi bảo vệ nếu vắng mặt..."></textarea>
                    </div>

                    <div class="nobi_form_group full-width">
                        <label class="nobi_form_check">
                            <input type="checkbox" name="is_default" value="1" {{ count($addresses) === 0 ? 'checked' : '' }}>
                            <span>Đặt làm địa chỉ nhận hàng mặc định</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="nobi_modal_footer">
                <button type="button" class="nobi_btn nobi_btn_secondary" onclick="closeModal('modalCreateAddress')">Hủy bỏ</button>
                <button type="submit" class="nobi_btn nobi_btn_primary"><i class="fas fa-save"></i> Lưu địa chỉ</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: CHỈNH SỬA ĐỊA CHỈ -->
<!-- ========================================== -->
<div id="modalEditAddress" class="nobi_modal_overlay" onclick="handleOverlayClick(event, 'modalEditAddress')">
    <div class="nobi_modal_card">
        <div class="nobi_modal_header">
            <h3><i class="fas fa-edit text-primary"></i> Cập nhật địa chỉ nhận hàng</h3>
            <button type="button" class="nobi_modal_close_btn" onclick="closeModal('modalEditAddress')" aria-label="Đóng">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="formEditAddress" method="POST" action="">
            @csrf
            <div class="nobi_modal_body">
                <div class="nobi_form_grid">
                    <div class="nobi_form_group">
                        <label class="nobi_form_label">Họ và tên người nhận <span class="req">*</span></label>
                        <input type="text" id="edit_full_name" name="full_name" class="nobi_form_input" required>
                    </div>

                    <div class="nobi_form_group">
                        <label class="nobi_form_label">Số điện thoại liên hệ <span class="req">*</span></label>
                        <input type="tel" id="edit_phone_number" name="phone_number" class="nobi_form_input" required pattern="(0|\+84)[0-9]{9,10}">
                    </div>

                    <div class="nobi_form_group full-width">
                        <label class="nobi_form_label">Địa chỉ chi tiết (Số nhà, tên ngõ/đường) <span class="req">*</span></label>
                        <input type="text" id="edit_detail_address" name="detail_address" class="nobi_form_input" required>
                    </div>

                    <div class="nobi_form_group three-cols">
                        <label class="nobi_form_label">Tỉnh / Thành phố <span class="req">*</span></label>
                        <input type="text" id="edit_province" name="province" class="nobi_form_input" required>
                    </div>

                    <div class="nobi_form_group three-cols">
                        <label class="nobi_form_label">Quận / Huyện <span class="req">*</span></label>
                        <input type="text" id="edit_district" name="district" class="nobi_form_input" required>
                    </div>

                    <div class="nobi_form_group three-cols">
                        <label class="nobi_form_label">Phường / Xã</label>
                        <input type="text" id="edit_ward" name="ward" class="nobi_form_input">
                    </div>

                    <div class="nobi_form_group three-cols">
                        <label class="nobi_form_label">Mã bưu chính (Postal Code) <span class="req">*</span></label>
                        <input type="text" id="edit_postal_code" name="postal_code" class="nobi_form_input" required>
                    </div>

                    <div class="nobi_form_group full-width">
                        <label class="nobi_form_label">Loại địa chỉ</label>
                        <select id="edit_address_type" name="address_type" class="nobi_form_select">
                            <option value="home">Nhà riêng (Giao tất cả các ngày trong tuần)</option>
                            <option value="work">Văn phòng / Công ty (Giao giờ hành chính)</option>
                        </select>
                    </div>

                    <div class="nobi_form_group full-width">
                        <label class="nobi_form_label">Ghi chú giao hàng (Không bắt buộc)</label>
                        <textarea id="edit_notes" name="notes" class="nobi_form_textarea"></textarea>
                    </div>

                    <div class="nobi_form_group full-width">
                        <label class="nobi_form_check">
                            <input type="checkbox" id="edit_is_default" name="is_default" value="1">
                            <span>Đặt làm địa chỉ nhận hàng mặc định</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="nobi_modal_footer">
                <button type="button" class="nobi_btn nobi_btn_secondary" onclick="closeModal('modalEditAddress')">Hủy bỏ</button>
                <button type="submit" class="nobi_btn nobi_btn_primary"><i class="fas fa-check"></i> Cập nhật địa chỉ</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateAddressModal() {
        document.getElementById('modalCreateAddress').classList.add('is-active');
        document.body.style.overflow = 'hidden';
    }

    function openEditAddressModal(address) {
        const form = document.getElementById('formEditAddress');
        // Route cập nhật: /profile/addresses/{address}/update
        form.action = "{{ url('profile/addresses') }}/" + address.id + "/update";

        document.getElementById('edit_full_name').value = address.full_name || '';
        document.getElementById('edit_phone_number').value = address.phone_number || '';
        document.getElementById('edit_detail_address').value = address.detail_address || '';
        document.getElementById('edit_province').value = address.province || '';
        document.getElementById('edit_district').value = address.district || '';
        document.getElementById('edit_ward').value = address.ward || '';
        document.getElementById('edit_postal_code').value = address.postal_code || '';
        document.getElementById('edit_address_type').value = address.address_type || 'home';
        document.getElementById('edit_notes').value = address.notes || '';
        document.getElementById('edit_is_default').checked = address.is_default == 1;

        document.getElementById('modalEditAddress').classList.add('is-active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('is-active');
            document.body.style.overflow = '';
        }
    }

    function handleOverlayClick(event, modalId) {
        if (event.target.id === modalId) {
            closeModal(modalId);
        }
    }

    // Lắng nghe phím ESC để đóng modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('modalCreateAddress');
            closeModal('modalEditAddress');
        }
    });
</script>
@endsection
