@extends('clients.layouts.master')

@section('title', 'Xin chào '. $account->name. ' đến với cửa hàng quần áo '. renderMeta($settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM'))

@section('head')
    <meta name="robots" content="follow, noindex"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --nobi-prof-bg: #f8fafc;
            --nobi-prof-card: #ffffff;
            --nobi-prof-border: #e2e8f0;
            --nobi-prof-primary: #0f172a;
            --nobi-prof-primary-hover: #1e293b;
            --nobi-prof-accent: #2563eb;
            --nobi-prof-accent-hover: #1d4ed8;
            --nobi-prof-danger: #ef4444;
            --nobi-prof-text-main: #0f172a;
            --nobi-prof-text-muted: #64748b;
            --nobi-prof-radius: 16px;
            --nobi-prof-radius-sm: 8px;
            --nobi-prof-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.04);
            --nobi-prof-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .nobifashion_profile_container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 16px 64px;
            font-family: var(--nobi-prof-font);
        }

        /* Top Header Bar */
        .nobifashion_profile_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
        }

        .nobifashion_profile_welcome_tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--nobi-prof-accent);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }

        .nobifashion_profile_header h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--nobi-prof-text-main);
            margin: 0;
            line-height: 1.3;
        }

        .nobifashion_profile_logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: #ffffff;
            color: var(--nobi-prof-danger);
            border: 1px solid #fee2e2;
            border-radius: var(--nobi-prof-radius-sm);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .nobifashion_profile_logout-btn:hover {
            background: var(--nobi-prof-danger);
            color: #ffffff;
            border-color: var(--nobi-prof-danger);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
            transform: translateY(-1px);
        }

        /* Main Card */
        .nobifashion_profile_card {
            background: var(--nobi-prof-card);
            border: 1px solid var(--nobi-prof-border);
            border-radius: var(--nobi-prof-radius);
            box-shadow: var(--nobi-prof-shadow);
            overflow: hidden;
            margin-bottom: 32px;
        }

        /* Card Header / Banner */
        .nobifashion_profile_card-header {
            position: relative;
            min-height: 220px;
            background-color: #1e293b;
            background-repeat: no-repeat;
            background-position: center;
            background-size: cover;
            display: flex;
            align-items: flex-end;
            padding: 32px 36px;
            box-sizing: border-box;
        }

        .nobifashion_profile_card-header::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.15) 0%, rgba(15, 23, 42, 0.78) 100%);
            z-index: 1;
            pointer-events: none;
        }

        .nobifashion_profile_header-content {
            position: relative;
            z-index: 2;
            width: 100%;
            display: flex;
            align-items: center;
            gap: 24px;
        }

        /* Avatar */
        .nobifashion_profile_avatar-upload {
            position: relative;
            width: 120px;
            height: 120px;
            flex-shrink: 0;
        }

        .nobifashion_profile_avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #ffffff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            background-color: #cbd5e1;
            box-sizing: border-box;
        }

        .nobifashion_profile_avatar-preview > div {
            width: 100%;
            height: 100%;
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }

        .nobifashion_profile_avatar-edit {
            position: absolute;
            right: 0;
            bottom: 4px;
            z-index: 4;
        }

        .nobifashion_profile_avatar-edit label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--nobi-prof-accent);
            color: #ffffff;
            border: 3px solid #ffffff;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
        }

        .nobifashion_profile_avatar-edit label:hover {
            background: var(--nobi-prof-accent-hover);
            transform: scale(1.08);
        }

        /* Header Details */
        .nobifashion_profile_header_details {
            color: #ffffff;
            flex: 1;
        }

        .nobifashion_profile_header_details h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 6px 0;
            color: #ffffff;
            text-shadow: 0 2px 6px rgba(0,0,0,0.4);
        }

        .nobifashion_profile_header_details .nobifashion_profile_header_email {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nobifashion_profile_header_details .nobifashion_profile_header_meta {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
            margin: 0 0 14px 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nobifashion_profile_header-bg-upload-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .nobifashion_profile_header-bg-upload-label:hover {
            background: #ffffff;
            color: var(--nobi-prof-text-main);
            border-color: #ffffff;
        }

        /* Nav Tabs */
        .nobifashion_profile_tabs {
            display: flex;
            background: #f8fafc;
            border-bottom: 1px solid var(--nobi-prof-border);
            padding: 0 24px;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .nobifashion_profile_tabs::-webkit-scrollbar {
            display: none;
        }

        .nobifashion_profile_tab-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 20px;
            font-size: 15px;
            font-weight: 600;
            color: var(--nobi-prof-text-muted);
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .nobifashion_profile_tab-link:hover {
            color: var(--nobi-prof-accent);
        }

        .nobifashion_profile_tab-link.active {
            color: var(--nobi-prof-accent);
            border-bottom-color: var(--nobi-prof-accent);
            background: #ffffff;
        }

        /* Tab Content */
        .nobifashion_profile_tab-content {
            display: none;
            padding: 32px 36px;
        }

        .nobifashion_profile_tab-content.active {
            display: block;
            animation: nobiProfFadeIn 0.25s ease-out forwards;
        }

        @keyframes nobiProfFadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form Layouts */
        .nobifashion_profile_grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px 24px;
        }

        .nobifashion_profile_form_group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nobifashion_profile_form_group.full-width {
            grid-column: 1 / -1;
        }

        .nobifashion_profile_form_label {
            font-size: 13.5px;
            font-weight: 600;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nobifashion_profile_form_label .required {
            color: var(--nobi-prof-danger);
            margin-left: 2px;
        }

        .nobifashion_profile_badge_locked {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11.5px;
            font-weight: 500;
            color: #64748b;
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 999px;
        }

        .nobifashion_profile_input_wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .nobifashion_profile_input,
        .nobifashion_profile_select,
        .nobifashion_profile_textarea {
            width: 100%;
            padding: 11px 16px;
            font-size: 14.5px;
            color: var(--nobi-prof-text-main);
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: var(--nobi-prof-radius-sm);
            box-sizing: border-box;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .nobifashion_profile_input:focus,
        .nobifashion_profile_select:focus,
        .nobifashion_profile_textarea:focus {
            outline: none;
            border-color: var(--nobi-prof-accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .nobifashion_profile_input:disabled,
        .nobifashion_profile_input.is-readonly {
            background-color: #f1f5f9 !important;
            color: #64748b !important;
            border-color: #e2e8f0 !important;
            cursor: not-allowed !important;
        }

        .nobifashion_profile_textarea {
            resize: vertical;
            min-height: 90px;
            line-height: 1.6;
        }

        .nobifashion_profile_field_hint {
            font-size: 12px;
            color: var(--nobi-prof-text-muted);
            margin-top: 4px;
        }

        .nobifashion_profile_field_error {
            font-size: 12px;
            color: var(--nobi-prof-danger);
            margin-top: 4px;
            min-height: 16px;
        }

        .nobifashion_profile_toggle-password {
            position: absolute;
            right: 14px;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            font-size: 15px;
            transition: color 0.15s ease;
        }

        .nobifashion_profile_toggle-password:hover {
            color: var(--nobi-prof-text-main);
        }

        /* Action Buttons */
        .nobifashion_profile_actions {
            margin-top: 28px;
            padding-top: 24px;
            border-top: 1px solid var(--nobi-prof-border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .nobifashion_profile_btn_primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 26px;
            font-size: 14.5px;
            font-weight: 600;
            color: #ffffff;
            background: var(--nobi-prof-accent);
            border: 1px solid var(--nobi-prof-accent);
            border-radius: var(--nobi-prof-radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }

        .nobifashion_profile_btn_primary:hover {
            background: var(--nobi-prof-accent-hover);
            border-color: var(--nobi-prof-accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
        }

        .nobifashion_profile_btn_secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            font-size: 14.5px;
            font-weight: 600;
            color: #334155;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: var(--nobi-prof-radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .nobifashion_profile_btn_secondary:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .nobifashion_profile_btn_link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--nobi-prof-accent);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .nobifashion_profile_btn_link:hover {
            color: var(--nobi-prof-accent-hover);
            text-decoration: underline;
        }

        /* Preference Sections */
        .nobifashion_pref_section {
            margin-bottom: 28px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--nobi-prof-border);
        }

        .nobifashion_pref_section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .nobifashion_pref_title {
            font-size: 16.5px;
            font-weight: 700;
            color: var(--nobi-prof-text-main);
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nobifashion_pref_list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .nobifashion_pref_item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--nobi-prof-radius-sm);
            transition: all 0.2s ease;
        }

        .nobifashion_pref_item:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .nobifashion_pref_item_info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .nobifashion_pref_item_label {
            font-size: 14.5px;
            font-weight: 600;
            color: #1e293b;
            cursor: pointer;
            margin: 0;
        }

        .nobifashion_pref_item_desc {
            font-size: 12.5px;
            color: var(--nobi-prof-text-muted);
            margin: 0;
        }

        /* Switch Toggle */
        .nobifashion_switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }

        .nobifashion_switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .nobifashion_slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background-color: #cbd5e1;
            transition: 0.25s;
            border-radius: 24px;
        }

        .nobifashion_slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.25s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        .nobifashion_switch input:checked + .nobifashion_slider {
            background-color: var(--nobi-prof-accent);
        }

        .nobifashion_switch input:checked + .nobifashion_slider:before {
            transform: translateX(20px);
        }

        /* Activity Card */
        .nobifashion_profile_activity {
            background: var(--nobi-prof-card);
            border: 1px solid var(--nobi-prof-border);
            border-radius: var(--nobi-prof-radius);
            box-shadow: var(--nobi-prof-shadow);
            overflow: hidden;
        }

        .nobifashion_profile_activity-header {
            padding: 24px 32px;
            border-bottom: 1px solid var(--nobi-prof-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .nobifashion_profile_activity-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--nobi-prof-text-main);
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nobifashion_profile_activity-header p {
            font-size: 13.5px;
            color: var(--nobi-prof-text-muted);
            margin: 0;
        }

        .nobifashion_activity_filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .nobifashion_activity_filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            font-size: 13.5px;
            font-weight: 500;
            color: #475569;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .nobifashion_activity_filter-btn:hover {
            color: var(--nobi-prof-accent);
            border-color: var(--nobi-prof-accent);
        }

        .nobifashion_activity_filter-btn.active {
            border-color: var(--nobi-prof-accent);
            color: #ffffff;
            background-color: var(--nobi-prof-accent);
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
        }

        .nobifashion_profile_activity-body {
            padding: 24px 32px;
        }

        /* Activity Items */
        .nobifashion_activity_row {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #f1f5f9;
            transition: all 0.2s ease;
            margin-bottom: 12px;
        }

        .nobifashion_activity_row:hover {
            background: #f8fafc;
            border-color: #e2e8f0;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
        }

        .nobifashion_activity_icon_wrap {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 16px;
        }

        .nobifashion_activity_content {
            flex: 1;
            min-width: 0;
        }

        .nobifashion_activity_main_row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .nobifashion_activity_item_title {
            font-size: 14.5px;
            font-weight: 600;
            color: var(--nobi-prof-text-main);
            margin: 0;
        }

        .nobifashion_activity_amount {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--nobi-prof-accent);
        }

        .nobifashion_activity_desc {
            font-size: 13px;
            color: var(--nobi-prof-text-muted);
            margin: 4px 0 8px 0;
            line-height: 1.45;
        }

        .nobifashion_activity_meta_row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .nobifashion_activity_time {
            font-size: 12px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .nobifashion_activity_badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 600;
            line-height: 1.2;
            letter-spacing: 0.02em;
        }

        .nobifashion_activity_action_link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--nobi-prof-accent);
            text-decoration: none;
            margin-top: 6px;
        }

        .nobifashion_activity_action_link:hover {
            text-decoration: underline;
        }

        /* Utility visibility */
        .hidden, .display_none {
            display: none !important;
        }

        /* Color classes cho icon và badge */
        .bg-blue-50 { background-color: #eff6ff !important; }
        .bg-blue-100 { background-color: #dbeafe !important; }
        .text-blue-500, .text-blue-600 { color: #2563eb !important; }

        .bg-gray-50 { background-color: #f8fafc !important; }
        .bg-gray-100 { background-color: #f1f5f9 !important; }
        .text-gray-400 { color: #94a3b8 !important; }
        .text-gray-500, .text-gray-600 { color: #64748b !important; }

        .bg-amber-50 { background-color: #fffbeb !important; }
        .bg-amber-100 { background-color: #fef3c7 !important; }
        .text-amber-500, .text-amber-600 { color: #d97706 !important; }

        .bg-red-50 { background-color: #fef2f2 !important; }
        .bg-red-100 { background-color: #fee2e2 !important; }
        .text-red-500, .text-red-600 { color: #dc2626 !important; }

        .bg-emerald-50 { background-color: #ecfdf5 !important; }
        .bg-emerald-100 { background-color: #d1fae5 !important; }
        .text-emerald-500, .text-emerald-600 { color: #059669 !important; }

        .bg-purple-50 { background-color: #faf5ff !important; }
        .bg-purple-100 { background-color: #f3e8ff !important; }
        .text-purple-500, .text-purple-600 { color: #9333ea !important; }

        .bg-indigo-50 { background-color: #eef2ff !important; }
        .bg-indigo-100 { background-color: #e0e7ff !important; }
        .text-indigo-500, .text-indigo-600 { color: #4f46e5 !important; }

        .bg-sky-50 { background-color: #f0f9ff !important; }
        .bg-sky-100 { background-color: #e0f2fe !important; }
        .text-sky-500, .text-sky-600 { color: #0284c7 !important; }
        .text-cyan-600 { color: #0891b2 !important; }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            .nobifashion_profile_container {
                padding: 16px 12px 48px;
            }

            .nobifashion_profile_header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .nobifashion_profile_card-header {
                min-height: 260px;
                padding: 24px 16px;
            }

            .nobifashion_profile_header-content {
                flex-direction: column;
                text-align: center;
                gap: 16px;
            }

            .nobifashion_profile_header_details .nobifashion_profile_header_email,
            .nobifashion_profile_header_details .nobifashion_profile_header_meta {
                justify-content: center;
            }

            .nobifashion_profile_grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .nobifashion_profile_tab-content {
                padding: 20px 16px;
            }

            .nobifashion_profile_activity-header,
            .nobifashion_profile_activity-body {
                padding: 18px 16px;
            }

            .nobifashion_profile_actions {
                flex-direction: column;
            }

            .nobifashion_profile_btn_primary,
            .nobifashion_profile_btn_secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <meta name="robots" content="follow, noindex"/>
@endsection

@section('foot')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            const tabLinks = document.querySelectorAll('.nobifashion_profile_tab-link');
            const tabContents = document.querySelectorAll('.nobifashion_profile_tab-content');
            
            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabLinks.forEach(l => l.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    const contentId = `nobifashion_profile_tab-${tabId}`;
                    const content = document.getElementById(contentId);
                    if (content) {
                        content.classList.add('active');
                    }
                });
            });

            // Password visibility toggle
            const togglePasswordButtons = document.querySelectorAll('.nobifashion_profile_toggle-password');
            togglePasswordButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            });

            // Avatar upload
            const avatarUpload = document.getElementById('nobifashion_profile_avatar-upload');
            if (avatarUpload) {
                avatarUpload.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.getElementById('nobifashion_profile_avatar-preview');
                            if (preview) {
                                preview.style.backgroundImage = `url(${e.target.result})`;
                            }
                        };
                        reader.readAsDataURL(this.files[0]);
                        
                        // Upload immediately
                        uploadAvatar('avatar', this.files[0]);
                    }
                });
            }

            // Sub Avatar upload
            const subAvatarUpload = document.getElementById('nobifashion_profile_sub_avatar-upload');
            if (subAvatarUpload) {
                subAvatarUpload.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                            const header = document.querySelector('.nobifashion_profile_card-header');
                            if (header) {
                                header.style.backgroundImage = `url(${e.target.result})`;
                            }
                    };
                    reader.readAsDataURL(this.files[0]);
                        
                        // Upload immediately
                        uploadAvatar('sub_avatar', this.files[0]);
                    }
                });
            }

            // Upload avatar function
            function uploadAvatar(type, file) {
                const formData = new FormData();
                formData.append(type, file);

                fetch('{{ route("client.profile.update") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast(data.message || 'Cập nhật ảnh thành công!', 'success');
                        } else {
                            alert(data.message || 'Cập nhật ảnh thành công!');
                        }
                        
                        // Update preview URLs if provided
                        if (data.profile) {
                            if (type === 'avatar' && data.profile.avatar_url) {
                                const preview = document.getElementById('nobifashion_profile_avatar-preview');
                                if (preview) {
                                    preview.style.backgroundImage = `url(${data.profile.avatar_url})`;
                                }
                            }
                            if (type === 'sub_avatar' && data.profile.sub_avatar_url) {
                                const header = document.querySelector('.nobifashion_profile_card-header');
                                if (header) {
                                    header.style.backgroundImage = `url(${data.profile.sub_avatar_url})`;
                                }
                            }
                        }
                    } else {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast(data.message || 'Có lỗi xảy ra khi cập nhật ảnh.', 'error');
                        } else {
                            alert(data.message || 'Có lỗi xảy ra khi cập nhật ảnh.');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Có lỗi xảy ra khi cập nhật ảnh.', 'error');
                    } else {
                        alert('Có lỗi xảy ra khi cập nhật ảnh.');
                    }
                });
            }

            // Form submissions
            const profileForm = document.getElementById('nobifashion_profile_form');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData();
                    
                    // Collect all form data
                    const fullName = document.getElementById('nobifashion_profile_first-name')?.value || '';
                    const nickname = document.getElementById('nobifashion_profile_last-name')?.value || '';
                    const bio = document.getElementById('nobifashion_profile_bio')?.value || '';
                    const gender = document.getElementById('nobifashion_profile_gender')?.value || '';
                    const birthday = document.getElementById('nobifashion_profile_dob')?.value || '';
                    const phone = document.getElementById('nobifashion_profile_phone')?.value || '';
                    
                    // Add to FormData
                    if (fullName) formData.append('full_name', fullName);
                    if (nickname) formData.append('nickname', nickname);
                    if (bio) formData.append('bio', bio);
                    if (gender) formData.append('gender', gender);
                    if (birthday) formData.append('birthday', birthday);
                    if (phone) formData.append('phone', phone);

                    fetch('{{ route("client.profile.update") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Cập nhật hồ sơ thành công!', 'success');
                            } else {
                                alert(data.message || 'Cập nhật hồ sơ thành công!');
                            }
                            
                            // Update display name if changed
                            if (data.profile && data.profile.full_name) {
                                const displayName = document.getElementById('nobifashion_profile_display-name');
                                if (displayName) {
                                    displayName.textContent = data.profile.full_name || data.profile.nickname || '{{ $account->name }}';
                                }
                            }
                        } else {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Có lỗi xảy ra khi cập nhật hồ sơ.', 'error');
                            } else {
                                alert(data.message || 'Có lỗi xảy ra khi cập nhật hồ sơ.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Có lỗi xảy ra khi cập nhật hồ sơ.', 'error');
                        } else {
                            alert('Có lỗi xảy ra khi cập nhật hồ sơ.');
                        }
                    });
                });
            }

            const passwordForm = document.getElementById('nobifashion_profile_password-form');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const currentPass = document.getElementById('nobifashion_profile_current-password')?.value || '';
                    const newPass = document.getElementById('nobifashion_profile_new-password')?.value || '';
                    const confirmPass = document.getElementById('nobifashion_profile_confirm-password')?.value || '';
                    
                    // 客户端验证
                    if (!currentPass) {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Vui lòng nhập mật khẩu hiện tại.', 'error');
                        } else {
                            alert('Vui lòng nhập mật khẩu hiện tại.');
                        }
                        return;
                    }
                    
                    if (!newPass) {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Vui lòng nhập mật khẩu mới.', 'error');
                        } else {
                            alert('Vui lòng nhập mật khẩu mới.');
                        }
                        return;
                    }
                    
                    if (newPass.length < 8) {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Mật khẩu mới phải có ít nhất 8 ký tự.', 'error');
                        } else {
                            alert('Mật khẩu mới phải có ít nhất 8 ký tự.');
                        }
                        return;
                    }
                    
                    if (newPass !== confirmPass) {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Xác nhận mật khẩu không khớp.', 'error');
                        } else {
                            alert('Xác nhận mật khẩu không khớp.');
                        }
                        return;
                    }
                    
                    // 提交到服务器
                    const formData = new FormData();
                    formData.append('current_password', currentPass);
                    formData.append('password', newPass);
                    formData.append('password_confirmation', confirmPass);
                    
                    // 禁用提交按钮
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn?.textContent || 'Lưu';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Đang xử lý...';
                    }
                    
                    fetch('{{ route("client.profile.change-password") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        return response.json().then(data => {
                            if (!response.ok) {
                                // 处理验证错误
                                if (data.errors) {
                                    // 清除之前的错误
                                    document.querySelectorAll('[id$="-error"]').forEach(el => el.textContent = '');
                                    
                                    // 显示新的错误
                                    Object.keys(data.errors).forEach(field => {
                                        const errorElement = document.getElementById(`nobifashion_profile_${field.replace('_', '-')}-error`);
                                        if (errorElement && data.errors[field]) {
                                            errorElement.textContent = Array.isArray(data.errors[field]) ? data.errors[field][0] : data.errors[field];
                                        }
                                    });
                                }
                                throw new Error(data.message || 'Có lỗi xảy ra khi đổi mật khẩu.');
                            }
                            return data;
                        });
                    })
                    .then(data => {
                        if (data.success) {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Đổi mật khẩu thành công!', 'success');
                            } else {
                                alert(data.message || 'Đổi mật khẩu thành công!');
                            }
                            
                            // 清除所有错误消息
                            document.querySelectorAll('[id$="-error"]').forEach(el => el.textContent = '');
                            
                            // 重置表单
                            passwordForm.reset();
                        } else {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Có lỗi xảy ra khi đổi mật khẩu.', 'error');
                            } else {
                                alert(data.message || 'Có lỗi xảy ra khi đổi mật khẩu.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        let errorMessage = 'Có lỗi xảy ra khi đổi mật khẩu.';
                        
                        if (error.message) {
                            errorMessage = error.message;
                        }
                        
                        if (typeof showCustomToast === 'function') {
                            showCustomToast(errorMessage, 'error');
                        } else {
                            alert(errorMessage);
                        }
                    })
                    .finally(() => {
                        // 恢复提交按钮
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                    });
                });
            }

            const preferencesForm = document.getElementById('nobifashion_profile_preferences-form');
            if (preferencesForm) {
                preferencesForm.addEventListener('submit', function(e) {
                e.preventDefault();
                    
                    const formData = new FormData();
                    
                    // 收集所有复选框和选择框的值
                    const checkboxes = preferencesForm.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(checkbox => {
                        formData.append(checkbox.name, checkbox.checked ? '1' : '0');
                    });
                    
                    const selects = preferencesForm.querySelectorAll('select');
                    selects.forEach(select => {
                        if (select.value) {
                            formData.append(select.name, select.value);
                        }
                    });
                    
                    // 禁用提交按钮
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn?.textContent || 'Lưu cài đặt';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...';
                    }
                    
                    fetch('{{ route("client.profile.preferences") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.message || 'Có lỗi xảy ra khi cập nhật cài đặt.');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Cập nhật cài đặt thành công!', 'success');
                            } else {
                                alert(data.message || 'Cập nhật cài đặt thành công!');
                            }
                        } else {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Có lỗi xảy ra khi cập nhật cài đặt.', 'error');
                            } else {
                                alert(data.message || 'Có lỗi xảy ra khi cập nhật cài đặt.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (typeof showCustomToast === 'function') {
                            showCustomToast(error.message || 'Có lỗi xảy ra khi cập nhật cài đặt.', 'error');
                        } else {
                            alert(error.message || 'Có lỗi xảy ra khi cập nhật cài đặt.');
                        }
                    })
                    .finally(() => {
                        // 恢复提交按钮
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Lưu cài đặt';
                        }
                    });
                });
                
                // 重置为默认值
                const resetBtn = document.getElementById('nobifashion_profile_reset-prefs-btn');
                if (resetBtn) {
                    resetBtn.addEventListener('click', function() {
                        if (confirm('Bạn có chắc chắn muốn đặt lại tất cả cài đặt về mặc định?')) {
                            // 重置所有复选框为默认值
                            const checkboxes = preferencesForm.querySelectorAll('input[type="checkbox"]');
                            checkboxes.forEach(checkbox => {
                                if (checkbox.name === 'notify_order_created' || 
                                    checkbox.name === 'notify_order_updated' || 
                                    checkbox.name === 'notify_order_shipped' || 
                                    checkbox.name === 'notify_order_completed' ||
                                    checkbox.name === 'notify_promotions' ||
                                    checkbox.name === 'notify_flash_sale' ||
                                    checkbox.name === 'notify_security' ||
                                    checkbox.name === 'notify_via_email' ||
                                    checkbox.name === 'notify_via_in_app' ||
                                    checkbox.name === 'show_order_history' ||
                                    checkbox.name === 'show_favorites') {
                                    checkbox.checked = true;
                                } else {
                                    checkbox.checked = false;
                                }
                            });
                            
                            // 重置选择框为默认值
                            const languageSelect = document.getElementById('nobifashion_preferred_language');
                            if (languageSelect) languageSelect.value = 'vi';
                            
                            const timezoneSelect = document.getElementById('nobifashion_preferred_timezone');
                            if (timezoneSelect) timezoneSelect.value = 'Asia/Ho_Chi_Minh';
                            
                            const currencySelect = document.getElementById('nobifashion_preferred_currency');
                            if (currencySelect) currencySelect.value = 'VND';
                            
                            if (typeof showCustomToast === 'function') {
                                showCustomToast('Đã đặt lại về mặc định. Vui lòng nhấn "Lưu cài đặt" để áp dụng.', 'info');
                            } else {
                                alert('Đã đặt lại về mặc định. Vui lòng nhấn "Lưu cài đặt" để áp dụng.');
                            }
                        }
                    });
                }
            }

            // Recent activities
            const activityState = {
                filter: 'all',
                page: 1,
                perPage: 5,
                hasMore: true,
                isLoading: false,
            };

            const activityListEl = document.getElementById('nobifashion_profile_activity-list');
            const activityEmptyEl = document.getElementById('nobifashion_profile_activity-empty');
            const activityLoadingEl = document.getElementById('nobifashion_profile_activity-loading');
            const activityLoadMoreBtn = document.getElementById('nobifashion_profile_activity-loadmore');
            const activityFilterButtons = document.querySelectorAll('.nobifashion_activity_filter-btn');

            function renderActivityBadges(badges = []) {
                if (!badges.length) {
                    return '';
                }

                return badges.map(badge => `
                    <span class="nobifashion_activity_badge ${badge.color || 'bg-gray-50 text-gray-600'}">
                        ${badge.text}
                    </span>
                `).join('');
            }

            function renderActivityItem(item) {
                const badgesHtml = renderActivityBadges(item.badges || []);
                const amountHtml = item.meta?.amount
                    ? `<span class="nobifashion_activity_amount">${item.meta.amount}</span>`
                    : '';
                const actionHtml = item.action_url
                    ? `<a href="${item.action_url}" class="nobifashion_activity_action_link">
                            ${item.action_text || 'Xem chi tiết'}
                            <i class="fas fa-chevron-right text-xs"></i>
                       </a>`
                    : '';

                return `
                    <div class="nobifashion_activity_row">
                        <div class="nobifashion_activity_icon_wrap ${item.icon_background || 'bg-gray-100'} ${item.icon_color || 'text-gray-500'}">
                            <i class="${item.icon || 'fas fa-info-circle'}"></i>
                        </div>
                        <div class="nobifashion_activity_content">
                            <div class="nobifashion_activity_main_row">
                                <h4 class="nobifashion_activity_item_title">${item.title || ''}</h4>
                                ${amountHtml}
                            </div>
                            <p class="nobifashion_activity_desc">${item.description || ''}</p>
                            <div class="nobifashion_activity_meta_row">
                                ${badgesHtml}
                                <div class="nobifashion_activity_time">
                                    <i class="far fa-clock"></i>
                                    <span title="${item.time_exact || ''}">${item.time_human || ''}</span>
                                </div>
                            </div>
                            ${actionHtml}
                        </div>
                    </div>
                `;
            }

            function toggleActivityEmptyState() {
                if (!activityListEl || !activityEmptyEl) {
                    return;
                }
                
                const hasItems = activityListEl.children.length > 0;
                activityEmptyEl.classList.toggle('hidden', hasItems);
            }

            function fetchActivities(reset = false) {
                if (!activityListEl || activityState.isLoading) {
                    return;
                }
                
                if (reset) {
                    activityState.page = 1;
                    activityState.hasMore = true;
                    activityListEl.innerHTML = '';
                }

                activityState.isLoading = true;
                activityLoadingEl?.classList.remove('hidden');

                const params = new URLSearchParams({
                    filter: activityState.filter,
                    page: activityState.page.toString(),
                    per_page: activityState.perPage.toString(),
                });

                fetch(`{{ route('client.profile.activities') }}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data?.success) {
                            throw new Error(data?.message || 'Không thể tải hoạt động.');
                        }

                        const activities = data.data || [];
                        if (reset) {
                            activityListEl.innerHTML = '';
                        }

                        activities.forEach(item => {
                            activityListEl.insertAdjacentHTML('beforeend', renderActivityItem(item));
                        });

                        toggleActivityEmptyState();

                        activityState.hasMore = data?.meta?.has_more ?? false;
                        if (activityLoadMoreBtn) {
                            activityLoadMoreBtn.classList.toggle('hidden', !activityState.hasMore);
                        }

                        if (activityState.hasMore) {
                            activityState.page += 1;
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        if (typeof showCustomToast === 'function') {
                            showCustomToast(error.message || 'Không thể tải hoạt động.', 'error');
                        }
                    })
                    .finally(() => {
                        activityState.isLoading = false;
                        activityLoadingEl?.classList.add('hidden');
                        toggleActivityEmptyState();
                    });
            }

            if (activityFilterButtons.length) {
                activityFilterButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        activityFilterButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        activityState.filter = this.getAttribute('data-activity-filter') || 'all';
                        fetchActivities(true);
                    });
                });
            }

            if (activityLoadMoreBtn) {
                activityLoadMoreBtn.addEventListener('click', function() {
                    if (activityState.hasMore) {
                        fetchActivities(false);
                    }
                });
            }

            if (activityListEl) {
                fetchActivities(true);
            }

            // Logout button
            const logoutBtn = document.querySelector('.nobifashion_profile_logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function() {
                    if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ route("client.auth.logout") }}';
                        
                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        form.appendChild(csrfToken);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
            
            // Display session messages
            @if (session('success'))
                if (typeof showCustomToast === 'function') {
                    showCustomToast(@json(session('success')), 'success');
                }
            @endif
            
            @if (session('error'))
                if (typeof showCustomToast === 'function') {
                    showCustomToast(@json(session('error')), 'error');
                }
            @endif
            
            @if (session('warning'))
                if (typeof showCustomToast === 'function') {
                    showCustomToast(@json(session('warning')), 'warning');
                }
            @endif

            // Enable 2FA button (nếu có trên giao diện)
            const enable2faBtn = document.querySelector('.nobifashion_profile_enable-2fa-btn');
            if (enable2faBtn) {
                enable2faBtn.addEventListener('click', function() {
                    alert('Two-factor authentication setup will begin. Check your email for next steps.');
                });
            }
        });
    </script>
@endsection

@section('content')
    @php
        $avatar = $account->profile->avatar ?? '';
        $subAvatar = $account->profile->sub_avatar ?? '';
        $avatarUrl = \Illuminate\Support\Str::startsWith($avatar, ['http://', 'https://']) ? $avatar : asset('clients/assets/img/' . (str_starts_with($avatar, 'users/') ? ltrim($avatar, '/') : 'users/' . ltrim($avatar, '/')));
        $subAvatarUrl = \Illuminate\Support\Str::startsWith($subAvatar, ['http://', 'https://']) ? $subAvatar : asset('clients/assets/img/' . (str_starts_with($subAvatar, 'users/') ? ltrim($subAvatar, '/') : 'users/' . ltrim($subAvatar, '/')));
    @endphp

    <div class="nobifashion_profile_container">
        <!-- Top Title & Logout Bar -->
        <div class="nobifashion_profile_header">
            <div>
                <div class="nobifashion_profile_welcome_tag">
                    <i class="fas fa-sparkles"></i> Tài khoản của tôi
                </div>
                <h1>Xin chào, {{ $account->name }}</h1>
            </div>
            <button type="button" class="nobifashion_profile_logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Đăng xuất</span>
            </button>
        </div>

        <!-- Main Profile Box Card -->
        <div class="nobifashion_profile_card">
            <!-- Cover Banner & Avatar Header -->
            <div class="nobifashion_profile_card-header" style="background-image: url('{{ $subAvatarUrl }}');">
                <div class="nobifashion_profile_header-content">
                    <div class="nobifashion_profile_avatar-upload">
                        <div class="nobifashion_profile_avatar-preview">
                            <div id="nobifashion_profile_avatar-preview" style="background-image: url('{{ $avatarUrl }}');"></div>
                        </div>
                        <div class="nobifashion_profile_avatar-edit">
                            <input type="file" id="nobifashion_profile_avatar-upload" class="display_none" accept="image/*">
                            <label for="nobifashion_profile_avatar-upload" title="Thay đổi ảnh đại diện">
                                <i class="fas fa-camera text-sm"></i>
                            </label>
                        </div>
                    </div>
                    
                    <div class="nobifashion_profile_header_details">
                        <h2 id="nobifashion_profile_display-name">{{ $account->profile->full_name ?? $account->name }}</h2>
                        <div class="nobifashion_profile_header_email">
                            <i class="fas fa-envelope"></i>
                            <span id="nobifashion_profile_email">{{ $account->email }}</span>
                        </div>
                        <div class="nobifashion_profile_header_meta">
                            <i class="far fa-calendar-alt"></i>
                            <span>Thành viên từ: <strong id="nobifashion_profile_join-date">Tháng {{ \Carbon\Carbon::parse($account->created_at)->month }} năm {{ \Carbon\Carbon::parse($account->created_at)->year }}</strong></span>
                        </div>
                        <div class="nobifashion_profile_header-info-actions">
                            <input type="file" id="nobifashion_profile_sub_avatar-upload" class="display_none" accept="image/*">
                            <label for="nobifashion_profile_sub_avatar-upload" class="nobifashion_profile_header-bg-upload-label">
                                <i class="fas fa-image"></i>
                                <span>Đổi ảnh bìa</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="nobifashion_profile_tabs">
                <button type="button" class="nobifashion_profile_tab-link active" data-tab="profile">
                    <i class="fas fa-user-circle"></i>
                    <span>Thông tin cá nhân</span>
                </button>
                <button type="button" class="nobifashion_profile_tab-link" data-tab="security">
                    <i class="fas fa-shield-alt"></i>
                    <span>Đổi mật khẩu</span>
                </button>
                <button type="button" class="nobifashion_profile_tab-link" data-tab="preferences">
                    <i class="fas fa-sliders-h"></i>
                    <span>Cài đặt & Thông báo</span>
                </button>
            </div>

            <!-- Tab 1: Thông tin cá nhân -->
            <div id="nobifashion_profile_tab-profile" class="nobifashion_profile_tab-content active">
                <form id="nobifashion_profile_form">
                    <div class="nobifashion_profile_grid">
                        <div class="nobifashion_profile_form_group">
                            <label class="nobifashion_profile_form_label" for="nobifashion_profile_first-name">
                                <span>Họ và tên của bạn <span class="required">*</span></span>
                            </label>
                            <input type="text" id="nobifashion_profile_first-name" name="full_name" class="nobifashion_profile_input" placeholder="Nhập họ và tên..." value="{{ $account->profile->full_name ?? '' }}">
                        </div>

                        <div class="nobifashion_profile_form_group">
                            <label class="nobifashion_profile_form_label" for="nobifashion_profile_last-name">
                                <span>Tên phụ</span>
                                <span class="nobifashion_profile_badge_locked"><i class="fas fa-lock text-xs"></i> Cố định</span>
                            </label>
                            <input type="text" id="nobifashion_profile_last-name" name="nickname" class="nobifashion_profile_input is-readonly" value="{{ $account->profile->nickname ?? '' }}" readonly title="Tên phụ không được phép chỉnh sửa">
                            <div class="nobifashion_profile_field_hint">Tên phụ được hệ thống cố định và không được phép chỉnh sửa.</div>
                        </div>

                        <div class="nobifashion_profile_form_group">
                            <label class="nobifashion_profile_form_label" for="nobifashion_profile_email-input">
                                <span>Địa chỉ Email</span>
                                <span class="nobifashion_profile_badge_locked"><i class="fas fa-lock text-xs"></i> Đã kích hoạt</span>
                            </label>
                            <input type="email" id="nobifashion_profile_email-input" class="nobifashion_profile_input is-readonly" value="{{ $account->email }}" disabled>
                        </div>

                        <div class="nobifashion_profile_form_group">
                            <label class="nobifashion_profile_form_label" for="nobifashion_profile_phone">
                                <span>Số điện thoại</span>
                            </label>
                            <input type="tel" id="nobifashion_profile_phone" name="phone" class="nobifashion_profile_input" placeholder="Ví dụ: 0912345678" value="{{ $account->profile->phone ?? '' }}">
                        </div>

                        <div class="nobifashion_profile_form_group">
                            <label class="nobifashion_profile_form_label" for="nobifashion_profile_dob">
                                <span>Ngày sinh</span>
                            </label>
                            <input type="date" id="nobifashion_profile_dob" name="birthday" class="nobifashion_profile_input" value="{{ $account->profile->birthday ? substr($account->profile->birthday, 0, 10) : '' }}">
                        </div>

                        <div class="nobifashion_profile_form_group">
                            <label class="nobifashion_profile_form_label" for="nobifashion_profile_gender">
                                <span>Giới tính</span>
                            </label>
                            <select id="nobifashion_profile_gender" name="gender" class="nobifashion_profile_select">
                                <option value="male" {{ ($account->profile->gender ?? '') === 'male' ? 'selected' : '' }}>Nam</option>
                                <option value="female" {{ ($account->profile->gender ?? '') === 'female' ? 'selected' : '' }}>Nữ</option>
                                <option value="other" {{ ($account->profile->gender ?? '') === 'other' ? 'selected' : '' }}>Khác</option>
                            </select>
                        </div>

                        <!-- Địa chỉ giao hàng mặc định -->
                        <div class="nobifashion_profile_form_group full-width">
                            @php
                                $addr = $account->addresses()->where('is_default', 1)->first() ?? $account->addresses()->first();
                                $fullAddress = $addr ? implode(' - ', array_filter([
                                    $addr->detail_address ?? $addr->detail ?? '',
                                    $addr->ward ?? '',
                                    $addr->district ?? '',
                                    $addr->province ?? '',
                                    $addr->country ?? '',
                                ])) : '';
                            @endphp
                            <div class="nobifashion_profile_form_label">
                                <span><i class="fas fa-map-marker-alt text-red-500 mr-1"></i> Địa chỉ giao hàng mặc định</span>
                                <a href="{{ route('client.addresses.index') }}" class="nobifashion_profile_btn_link">
                                    <i class="fas fa-cog text-xs"></i> Quản lý sổ địa chỉ
                                </a>
                            </div>
                            <textarea disabled id="nobifashion_profile_address" class="nobifashion_profile_textarea is-readonly" rows="2" placeholder="Chưa có địa chỉ mặc định. Vui lòng bấm 'Quản lý sổ địa chỉ' để thêm.">{{ trim($fullAddress) }}</textarea>
                        </div>

                        <!-- Giới thiệu bản thân -->
                        <div class="nobifashion_profile_form_group full-width">
                            <label class="nobifashion_profile_form_label" for="nobifashion_profile_bio">
                                <span>Giới thiệu bản thân</span>
                            </label>
                            <textarea id="nobifashion_profile_bio" name="bio" class="nobifashion_profile_textarea" rows="3" placeholder="Chia sẻ đôi nét về phong cách thời trang hoặc sở thích của bạn...">{{ $account->profile->bio ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="nobifashion_profile_actions">
                        <button type="submit" class="nobifashion_profile_btn_primary nobifashion_profile_save-btn">
                            <i class="fas fa-check"></i>
                            <span>Lưu thay đổi</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab 2: Đổi mật khẩu -->
            <div id="nobifashion_profile_tab-security" class="nobifashion_profile_tab-content">
                <form id="nobifashion_profile_password-form">
                    <div style="max-width: 560px;">
                        <h3 class="nobifashion_pref_title">
                            <i class="fas fa-key text-blue-500"></i> Cập nhật mật khẩu mới
                        </h3>
                        <p class="nobifashion_profile_field_hint" style="margin-bottom: 20px;">
                            Để bảo vệ tài khoản, hãy sử dụng mật khẩu mạnh dài ít nhất 8 ký tự, bao gồm cả chữ số và ký tự đặc biệt.
                        </p>

                        <div class="nobifashion_profile_form_group" style="margin-bottom: 16px;">
                            <label class="nobifashion_profile_form_label" for="nobifashion_profile_current-password">
                                <span>Mật khẩu hiện tại <span class="required">*</span></span>
                            </label>
                            <div class="nobifashion_profile_input_wrap">
                                <input type="password" id="nobifashion_profile_current-password" name="current_password" class="nobifashion_profile_input" placeholder="Nhập mật khẩu hiện tại..." required>
                                <button type="button" class="nobifashion_profile_toggle-password" title="Ẩn/Hiện mật khẩu">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="nobifashion_profile_field_error" id="nobifashion_profile_current-password-error"></div>
                        </div>

                        <div class="nobifashion_profile_form_group" style="margin-bottom: 16px;">
                            <label class="nobifashion_profile_form_label" for="nobifashion_profile_new-password">
                                <span>Mật khẩu mới <span class="required">*</span></span>
                            </label>
                            <div class="nobifashion_profile_input_wrap">
                                <input type="password" id="nobifashion_profile_new-password" name="password" class="nobifashion_profile_input" placeholder="Nhập mật khẩu mới..." required>
                                <button type="button" class="nobifashion_profile_toggle-password" title="Ẩn/Hiện mật khẩu">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="nobifashion_profile_field_error" id="nobifashion_profile_new-password-error"></div>
                        </div>

                        <div class="nobifashion_profile_form_group" style="margin-bottom: 16px;">
                            <label class="nobifashion_profile_form_label" for="nobifashion_profile_confirm-password">
                                <span>Xác nhận mật khẩu mới <span class="required">*</span></span>
                            </label>
                            <div class="nobifashion_profile_input_wrap">
                                <input type="password" id="nobifashion_profile_confirm-password" name="password_confirmation" class="nobifashion_profile_input" placeholder="Nhập lại mật khẩu mới..." required>
                                <button type="button" class="nobifashion_profile_toggle-password" title="Ẩn/Hiện mật khẩu">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="nobifashion_profile_field_error" id="nobifashion_profile_confirm-password-error"></div>
                        </div>

                        <div class="nobifashion_profile_actions" style="justify-content: flex-start;">
                            <button type="submit" class="nobifashion_profile_btn_primary nobifashion_profile_update-password-btn">
                                <i class="fas fa-lock"></i>
                                <span>Đổi mật khẩu</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tab 3: Cài đặt & Thông báo -->
            <div id="nobifashion_profile_tab-preferences" class="nobifashion_profile_tab-content">
                <form id="nobifashion_profile_preferences-form">
                    @php
                        $profile = $account->profile;
                        $notifyOrderCreated = $profile->notify_order_created ?? true;
                        $notifyOrderUpdated = $profile->notify_order_updated ?? true;
                        $notifyOrderShipped = $profile->notify_order_shipped ?? true;
                        $notifyOrderCompleted = $profile->notify_order_completed ?? true;
                        $notifyPromotions = $profile->notify_promotions ?? true;
                        $notifyFlashSale = $profile->notify_flash_sale ?? true;
                        $notifyNewProducts = $profile->notify_new_products ?? false;
                        $notifyStockAlert = $profile->notify_stock_alert ?? false;
                        $notifySecurity = $profile->notify_security ?? true;
                        $notifyViaEmail = $profile->notify_via_email ?? true;
                        $notifyViaSms = $profile->notify_via_sms ?? false;
                        $notifyViaInApp = $profile->notify_via_in_app ?? true;
                        $showOrderHistory = $profile->show_order_history ?? true;
                        $showFavorites = $profile->show_favorites ?? true;
                        $preferredLanguage = $profile->preferred_language ?? 'vi';
                        $preferredTimezone = $profile->preferred_timezone ?? 'Asia/Ho_Chi_Minh';
                        $preferredCurrency = $profile->preferred_currency ?? 'VND';
                    @endphp

                    <!-- Nhóm 1: Thông báo đơn hàng -->
                    <div class="nobifashion_pref_section">
                        <h4 class="nobifashion_pref_title">
                            <i class="fas fa-shopping-bag text-blue-500"></i> Thông báo đơn hàng
                        </h4>
                        <div class="nobifashion_pref_list">
                            <div class="nobifashion_pref_item">
                                <div class="nobifashion_pref_item_info">
                                    <label class="nobifashion_pref_item_label" for="nobifashion_notify_order_created">Xác nhận đơn hàng mới</label>
                                    <span class="nobifashion_pref_item_desc">Nhận thông báo ngay khi đặt hàng thành công</span>
                                </div>
                                <label class="nobifashion_switch">
                                    <input type="checkbox" id="nobifashion_notify_order_created" name="notify_order_created" value="1" {{ $notifyOrderCreated ? 'checked' : '' }}>
                                    <span class="nobifashion_slider"></span>
                                </label>
                            </div>

                            <div class="nobifashion_pref_item">
                                <div class="nobifashion_pref_item_info">
                                    <label class="nobifashion_pref_item_label" for="nobifashion_notify_order_updated">Cập nhật trạng thái đơn hàng</label>
                                    <span class="nobifashion_pref_item_desc">Thông báo khi đơn hàng được chuẩn bị hoặc xử lý</span>
                                </div>
                                <label class="nobifashion_switch">
                                    <input type="checkbox" id="nobifashion_notify_order_updated" name="notify_order_updated" value="1" {{ $notifyOrderUpdated ? 'checked' : '' }}>
                                    <span class="nobifashion_slider"></span>
                                </label>
                            </div>

                            <div class="nobifashion_pref_item">
                                <div class="nobifashion_pref_item_info">
                                    <label class="nobifashion_pref_item_label" for="nobifashion_notify_order_shipped">Thông báo vận chuyển</label>
                                    <span class="nobifashion_pref_item_desc">Cập nhật vị trí kiện hàng và tài xế đang giao</span>
                                </div>
                                <label class="nobifashion_switch">
                                    <input type="checkbox" id="nobifashion_notify_order_shipped" name="notify_order_shipped" value="1" {{ $notifyOrderShipped ? 'checked' : '' }}>
                                    <span class="nobifashion_slider"></span>
                                </label>
                            </div>

                            <div class="nobifashion_pref_item">
                                <div class="nobifashion_pref_item_info">
                                    <label class="nobifashion_pref_item_label" for="nobifashion_notify_order_completed">Đơn hàng hoàn thành</label>
                                    <span class="nobifashion_pref_item_desc">Gợi ý đánh giá sản phẩm và tích điểm thành viên</span>
                                </div>
                                <label class="nobifashion_switch">
                                    <input type="checkbox" id="nobifashion_notify_order_completed" name="notify_order_completed" value="1" {{ $notifyOrderCompleted ? 'checked' : '' }}>
                                    <span class="nobifashion_slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Nhóm 2: Khuyến mãi & Ưu đãi -->
                    <div class="nobifashion_pref_section">
                        <h4 class="nobifashion_pref_title">
                            <i class="fas fa-tag text-emerald-500"></i> Khuyến mãi & Ưu đãi
                        </h4>
                        <div class="nobifashion_pref_list">
                            <div class="nobifashion_pref_item">
                                <div class="nobifashion_pref_item_info">
                                    <label class="nobifashion_pref_item_label" for="nobifashion_notify_promotions">Khuyến mãi & Mã giảm giá</label>
                                    <span class="nobifashion_pref_item_desc">Nhận voucher độc quyền dành riêng cho thành viên</span>
                                </div>
                                <label class="nobifashion_switch">
                                    <input type="checkbox" id="nobifashion_notify_promotions" name="notify_promotions" value="1" {{ $notifyPromotions ? 'checked' : '' }}>
                                    <span class="nobifashion_slider"></span>
                                </label>
                            </div>

                            <div class="nobifashion_pref_item">
                                <div class="nobifashion_pref_item_info">
                                    <label class="nobifashion_pref_item_label" for="nobifashion_notify_flash_sale">Flash Sale & Deal chớp nhoáng</label>
                                    <span class="nobifashion_pref_item_desc">Báo trước khi các đợt giảm giá sâu bắt đầu</span>
                                </div>
                                <label class="nobifashion_switch">
                                    <input type="checkbox" id="nobifashion_notify_flash_sale" name="notify_flash_sale" value="1" {{ $notifyFlashSale ? 'checked' : '' }}>
                                    <span class="nobifashion_slider"></span>
                                </label>
                            </div>

                            <div class="nobifashion_pref_item">
                                <div class="nobifashion_pref_item_info">
                                    <label class="nobifashion_pref_item_label" for="nobifashion_notify_new_products">Bộ sưu tập & Sản phẩm mới</label>
                                    <span class="nobifashion_pref_item_desc">Cập nhật các thiết kế thời trang vừa lên kệ</span>
                                </div>
                                <label class="nobifashion_switch">
                                    <input type="checkbox" id="nobifashion_notify_new_products" name="notify_new_products" value="1" {{ $notifyNewProducts ? 'checked' : '' }}>
                                    <span class="nobifashion_slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Nhóm 3: Phương thức nhận thông báo -->
                    <div class="nobifashion_pref_section">
                        <h4 class="nobifashion_pref_title">
                            <i class="fas fa-bell text-indigo-500"></i> Kênh nhận thông báo
                        </h4>
                        <div class="nobifashion_pref_list">
                            <div class="nobifashion_pref_item">
                                <div class="nobifashion_pref_item_info">
                                    <label class="nobifashion_pref_item_label" for="nobifashion_notify_via_email"><i class="fas fa-envelope text-xs mr-1"></i> Qua Email</label>
                                    <span class="nobifashion_pref_item_desc">Gửi thông tin đơn hàng và hóa đơn đến hộp thư</span>
                                </div>
                                <label class="nobifashion_switch">
                                    <input type="checkbox" id="nobifashion_notify_via_email" name="notify_via_email" value="1" {{ $notifyViaEmail ? 'checked' : '' }}>
                                    <span class="nobifashion_slider"></span>
                                </label>
                            </div>

                            <div class="nobifashion_pref_item">
                                <div class="nobifashion_pref_item_info">
                                    <label class="nobifashion_pref_item_label" for="nobifashion_notify_via_in_app"><i class="fas fa-desktop text-xs mr-1"></i> Trên trình duyệt & Website</label>
                                    <span class="nobifashion_pref_item_desc">Hiển thị popup thông báo trực tiếp khi đang online</span>
                                </div>
                                <label class="nobifashion_switch">
                                    <input type="checkbox" id="nobifashion_notify_via_in_app" name="notify_via_in_app" value="1" {{ $notifyViaInApp ? 'checked' : '' }}>
                                    <span class="nobifashion_slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Nhóm 4: Ngôn ngữ & Tiền tệ -->
                    <div class="nobifashion_pref_section">
                        <h4 class="nobifashion_pref_title">
                            <i class="fas fa-globe text-cyan-600"></i> Định dạng vùng & Ngôn ngữ
                        </h4>
                        <div class="nobifashion_profile_grid">
                            <div class="nobifashion_profile_form_group">
                                <label class="nobifashion_profile_form_label" for="nobifashion_preferred_language">
                                    <span>Ngôn ngữ hiển thị</span>
                                </label>
                                <select id="nobifashion_preferred_language" name="preferred_language" class="nobifashion_profile_select">
                                    <option value="vi" {{ $preferredLanguage === 'vi' ? 'selected' : '' }}>Tiếng Việt (Mặc định)</option>
                                    <option value="en" {{ $preferredLanguage === 'en' ? 'selected' : '' }}>English</option>
                                </select>
                            </div>

                            <div class="nobifashion_profile_form_group">
                                <label class="nobifashion_profile_form_label" for="nobifashion_preferred_currency">
                                    <span>Đơn vị tiền tệ</span>
                                </label>
                                <select id="nobifashion_preferred_currency" name="preferred_currency" class="nobifashion_profile_select">
                                    <option value="VND" {{ $preferredCurrency === 'VND' ? 'selected' : '' }}>VND (₫)</option>
                                    <option value="USD" {{ $preferredCurrency === 'USD' ? 'selected' : '' }}>USD ($)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="nobifashion_profile_actions">
                        <button type="button" id="nobifashion_profile_reset-prefs-btn" class="nobifashion_profile_btn_secondary">
                            <i class="fas fa-undo"></i>
                            <span>Khôi phục mặc định</span>
                        </button>
                        <button type="submit" class="nobifashion_profile_btn_primary">
                            <i class="fas fa-save"></i>
                            <span>Lưu cài đặt</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Activities Box -->
        <div class="nobifashion_profile_activity">
            <div class="nobifashion_profile_activity-header">
                <div>
                    <h3>
                        <i class="fas fa-history text-blue-500"></i> Hoạt động gần đây
                    </h3>
                    <p>Theo dõi lịch sử đăng nhập, bảo mật và các cập nhật đơn hàng của bạn.</p>
                </div>
                <div class="nobifashion_activity_filters">
                    <button type="button" class="nobifashion_activity_filter-btn active" data-activity-filter="all">
                        <i class="fas fa-layer-group"></i> Tất cả
                    </button>
                    <button type="button" class="nobifashion_activity_filter-btn" data-activity-filter="account">
                        <i class="fas fa-user-circle"></i> Hồ sơ
                    </button>
                    <button type="button" class="nobifashion_activity_filter-btn" data-activity-filter="security">
                        <i class="fas fa-shield-alt"></i> Bảo mật
                    </button>
                    <button type="button" class="nobifashion_activity_filter-btn" data-activity-filter="order">
                        <i class="fas fa-truck"></i> Đơn hàng
                    </button>
                </div>
            </div>

            <div class="nobifashion_profile_activity-body">
                <div id="nobifashion_profile_activity-loading" class="hidden" style="text-align: center; padding: 24px 0; color: #64748b;">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Đang tải dữ liệu hoạt động...
                </div>
                <div id="nobifashion_profile_activity-list"></div>
                <div id="nobifashion_profile_activity-empty" class="hidden" style="text-align: center; padding: 36px 0; color: #94a3b8;">
                    <div style="font-size: 32px; margin-bottom: 8px;"><i class="fas fa-inbox"></i></div>
                    <p style="margin: 0; font-size: 14px;">Chưa có hoạt động nào được ghi nhận.</p>
                </div>
                <div style="text-align: center; margin-top: 16px;">
                    <button type="button" id="nobifashion_profile_activity-loadmore" class="nobifashion_profile_btn_secondary hidden">
                        <span>Tải thêm hoạt động</span>
                        <i class="fas fa-chevron-down text-xs ml-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="custom-toast-container" class="custom-toast-container"></div>
@endsection
