<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập Quản trị - NobiFashion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/icon-admin.gif') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('admins/img/icons/icon-admin.gif') }}" type="image/gif">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="googlebot" content="noindex, nofollow, noarchive">
    <meta name="bingbot" content="noindex, nofollow, noarchive">
    <meta name="slurp" content="noindex, nofollow, noarchive">
    <meta name="duckduckbot" content="noindex, nofollow, noarchive">
    <style>
        :root {
            --bg-color: #6a0035;
            --blob-grad: linear-gradient(145deg, #a855f7 0%, #c026d3 28%, #e11d48 55%, #f97316 82%, #fbbf24 100%);
            --btn-grad: linear-gradient(90deg, #ff3366 0%, #ff5e36 45%, #ffb703 100%);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(circle at 15% 20%, rgba(217, 70, 239, 0.32) 0%, transparent 45%),
                radial-gradient(circle at 85% 75%, rgba(249, 115, 22, 0.25) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(106, 0, 53, 1) 0%, rgba(72, 0, 36, 1) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
            position: relative;
        }

        /* Ambient floating orbs */
        .ambient-glow-1 {
            position: absolute;
            top: 8%;
            left: 5%;
            width: 360px;
            height: 360px;
            background: radial-gradient(circle, rgba(0, 229, 255, 0.22) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(50px);
            pointer-events: none;
            z-index: 0;
            animation: pulseGlow 8s ease-in-out infinite alternate;
        }

        .ambient-glow-2 {
            position: absolute;
            bottom: 5%;
            right: 10%;
            width: 420px;
            height: 420px;
            background: radial-gradient(circle, rgba(255, 94, 54, 0.18) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
            z-index: 0;
        }

        @keyframes pulseGlow {
            0% { transform: scale(0.9) translate(0, 0); opacity: 0.6; }
            100% { transform: scale(1.15) translate(20px, -15px); opacity: 1; }
        }

        .login-container {
            width: 100%;
            max-width: 1240px;
            display: grid;
            grid-template-columns: 500px 1fr;
            align-items: center;
            gap: 50px;
            position: relative;
            z-index: 1;
        }

        /* ================= CỘT TRÁI: FORM ĐĂNG NHẬP + MẤY HÌNH BÊN DƯỚI TO RÕ ================= */
        .left-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .blob-wrapper {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Neon Cyan Glowing Blob Behind */
        .blob-glow {
            position: absolute;
            inset: -14px -18px -18px -14px;
            background: linear-gradient(135deg, #00f0ff 0%, #0077fe 50%, #00e5ff 100%);
            border-radius: 46% 54% 68% 32% / 42% 58% 42% 58%;
            filter: blur(16px);
            opacity: 0.88;
            z-index: 1;
            animation: morphGlow 14s ease-in-out infinite alternate;
        }

        /* Main Liquid Organic Blob Card */
        .blob-card {
            position: relative;
            z-index: 2;
            width: 100%;
            background: var(--blob-grad);
            border-radius: 42% 58% 70% 30% / 45% 45% 55% 55%;
            padding: 86px 94px;
            box-shadow: 
                inset 0 2px 14px rgba(255, 255, 255, 0.45),
                inset 0 -4px 16px rgba(0, 0, 0, 0.25),
                0 30px 60px -10px rgba(0, 0, 0, 0.55);
            animation: morphCard 14s ease-in-out infinite alternate;
            transition: all 0.4s ease;
        }

        @keyframes morphCard {
            0% {
                border-radius: 42% 58% 70% 30% / 45% 45% 55% 55%;
                transform: translate(0, 0) rotate(0deg);
            }
            33% {
                border-radius: 54% 46% 38% 62% / 58% 38% 62% 42%;
                transform: translate(3px, -3px) rotate(0.8deg);
            }
            66% {
                border-radius: 36% 64% 62% 38% / 42% 64% 36% 58%;
                transform: translate(-3px, 4px) rotate(-0.8deg);
            }
            100% {
                border-radius: 60% 40% 45% 55% / 52% 44% 56% 48%;
                transform: translate(2px, -2px) rotate(0.4deg);
            }
        }

        @keyframes morphGlow {
            0% {
                border-radius: 46% 54% 68% 32% / 42% 58% 42% 58%;
                transform: scale(1) rotate(0deg);
            }
            50% {
                border-radius: 62% 38% 42% 58% / 54% 46% 54% 46%;
                transform: scale(1.02) rotate(2deg);
            }
            100% {
                border-radius: 38% 62% 58% 42% / 46% 60% 40% 54%;
                transform: scale(0.99) rotate(-2deg);
            }
        }

        .blob-title {
            color: #ffffff;
            font-size: 34px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.25);
        }

        /* Error Notification */
        .error-badge {
            background: rgba(220, 38, 38, 0.5);
            backdrop-filter: blur(10px);
            border: 1.5px solid rgba(254, 202, 202, 0.7);
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Input Groups */
        .input-group {
            position: relative;
            margin-bottom: 18px;
        }

        .pill-input {
            width: 100%;
            height: 52px;
            padding: 0 48px 0 24px;
            background: rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1.5px solid rgba(255, 255, 255, 0.6);
            border-radius: 50px;
            color: #ffffff;
            font-size: 15.5px;
            font-family: inherit;
            outline: none;
            transition: all 0.25s ease;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .pill-input::placeholder {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 400;
        }

        .pill-input:focus {
            background: rgba(255, 255, 255, 0.32);
            border-color: #ffffff;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }

        .input-icon-right {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.85);
            font-size: 17px;
            cursor: pointer;
            transition: color 0.2s ease;
            background: none;
            border: none;
            padding: 4px;
        }

        .input-icon-right:hover {
            color: #ffffff;
        }

        /* Pill Submit Button */
        .pill-submit-btn {
            width: 100%;
            height: 52px;
            margin-top: 10px;
            border: none;
            border-radius: 50px;
            background: var(--btn-grad);
            color: #ffffff;
            font-size: 16.5px;
            font-weight: 700;
            font-family: inherit;
            letter-spacing: 0.5px;
            cursor: pointer;
            box-shadow: 
                0 8px 25px rgba(255, 51, 102, 0.45),
                inset 0 1px 2px rgba(255, 255, 255, 0.45);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .pill-submit-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.08);
            box-shadow: 
                0 14px 30px rgba(255, 51, 102, 0.6),
                inset 0 1px 3px rgba(255, 255, 255, 0.5);
        }

        .pill-submit-btn:active {
            transform: translateY(0);
        }

        /* Remember Me & Links */
        .form-footer-options {
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.95);
        }

        .custom-remember {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }

        .custom-remember input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 18px;
            height: 18px;
            border: 1.5px solid rgba(255, 255, 255, 0.75);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.18);
            cursor: pointer;
            display: grid;
            place-content: center;
            transition: all 0.2s ease;
        }

        .custom-remember input[type="checkbox"]:checked {
            background: #ffffff;
            border-color: #ffffff;
        }

        .custom-remember input[type="checkbox"]:checked::before {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 11px;
            color: #e11d48;
        }

        .footer-links {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.92);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .footer-link:hover {
            color: #ffffff;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
            transform: translateX(2px);
        }

        /* ===== MẤY HÌNH BÊN DƯỚI TO RA: CÁC THẺ HUY HIỆU 3D GLASS RÕ NÉT ===== */
        .left-badges-grid {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 26px;
        }

        .badge-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            border-radius: 18px;
            padding: 16px 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: default;
        }

        .badge-card:hover {
            transform: translateY(-6px) scale(1.03);
            background: rgba(255, 255, 255, 0.18);
            border-color: rgba(255, 255, 255, 0.55);
            box-shadow: 0 18px 35px rgba(0, 0, 0, 0.35);
        }

        .badge-icon-box {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.28);
            transition: transform 0.3s ease;
        }

        .badge-card:hover .badge-icon-box {
            transform: scale(1.1) rotate(5deg);
        }

        .icon-box-laravel {
            background: linear-gradient(135deg, #ff3b30 0%, #b31217 100%);
            box-shadow: 0 8px 20px rgba(255, 59, 48, 0.45);
        }

        .icon-box-security {
            background: linear-gradient(135deg, #00d2ff 0%, #0066ff 100%);
            box-shadow: 0 8px 20px rgba(0, 210, 255, 0.45);
        }

        .icon-box-speed {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.45);
        }

        .badge-card-title {
            font-size: 13.5px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .badge-card-desc {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.75);
            font-weight: 500;
            line-height: 1.2;
        }

        /* ================= CỘT PHẢI: HOẠT HỌA 3D DASHBOARD THỜI TRANG ĐẸP MẮT & Ý NGHĨA ================= */
        .showcase-column {
            position: relative;
            padding-left: 20px;
        }

        .showcase-header {
            margin-bottom: 30px;
        }

        .showcase-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            color: #fde047;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .showcase-title {
            color: #ffffff;
            font-size: 40px;
            font-weight: 800;
            line-height: 1.15;
            margin-top: 14px;
            letter-spacing: -1px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .showcase-title-highlight {
            background: linear-gradient(90deg, #ff4d6d 0%, #ff8a00 50%, #facc15 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: block;
        }

        .showcase-desc {
            color: rgba(255, 255, 255, 0.85);
            font-size: 15.5px;
            line-height: 1.5;
            margin-top: 10px;
            max-width: 520px;
        }

        /* Sân khấu 3D Dashboard Mockup */
        .dashboard-stage {
            position: relative;
            width: 100%;
            height: 380px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Vòng hào quang xoay chậm */
        .orbit-ring {
            position: absolute;
            width: 440px;
            height: 320px;
            border-radius: 50%;
            border: 2px dashed rgba(255, 255, 255, 0.2);
            pointer-events: none;
            animation: rotateOrbit 30s linear infinite;
        }

        @keyframes rotateOrbit {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Thẻ chính trung tâm (Live Analytics Board) */
        .main-dashboard-card {
            position: relative;
            z-index: 2;
            width: 360px;
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            border-radius: 24px;
            padding: 24px;
            box-shadow: 
                0 25px 50px -10px rgba(0, 0, 0, 0.5),
                inset 0 1px 3px rgba(255, 255, 255, 0.4);
            animation: floatCenter 6s ease-in-out infinite alternate;
        }

        @keyframes floatCenter {
            0% { transform: translateY(0px) rotate(0deg); }
            100% { transform: translateY(-12px) rotate(1deg); }
        }

        .card-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .card-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-logo-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, #ec4899 0%, #f97316 100%);
            display: grid;
            place-content: center;
            color: #ffffff;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(236, 72, 153, 0.4);
        }

        .brand-text {
            font-size: 14.5px;
            font-weight: 700;
            color: #ffffff;
        }

        .live-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: rgba(16, 185, 129, 0.25);
            border: 1px solid rgba(52, 211, 153, 0.5);
            border-radius: 20px;
            color: #34d399;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .live-dot {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10b981;
            animation: pulseDot 1.5s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.8); }
        }

        /* Mini Chart Columns Animation */
        .chart-visual-box {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .chart-stats-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
        }

        .stat-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.75);
            margin-bottom: 2px;
        }

        .stat-growth {
            font-size: 12px;
            font-weight: 700;
            color: #34d399;
            background: rgba(16, 185, 129, 0.2);
            padding: 3px 8px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .animated-bars {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 52px;
            gap: 6px;
        }

        .chart-bar {
            flex: 1;
            background: linear-gradient(180deg, #ec4899 0%, #f97316 100%);
            border-radius: 6px;
            animation: barWave 2s ease-in-out infinite alternate;
        }

        .chart-bar:nth-child(1) { height: 45%; animation-delay: 0.1s; }
        .chart-bar:nth-child(2) { height: 75%; animation-delay: 0.3s; }
        .chart-bar:nth-child(3) { height: 60%; animation-delay: 0.5s; }
        .chart-bar:nth-child(4) { height: 95%; animation-delay: 0.2s; background: linear-gradient(180deg, #00f0ff 0%, #0077ff 100%); }
        .chart-bar:nth-child(5) { height: 50%; animation-delay: 0.4s; }
        .chart-bar:nth-child(6) { height: 85%; animation-delay: 0.6s; }
        .chart-bar:nth-child(7) { height: 100%; animation-delay: 0.3s; }

        @keyframes barWave {
            0% { transform: scaleY(0.7); }
            100% { transform: scaleY(1.05); }
        }

        .card-metrics-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .metric-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .metric-title {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.75);
            font-weight: 500;
        }

        .metric-num {
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            margin-top: 2px;
        }

        /* 3 Thẻ bay vệ tinh xung quanh (Floating Orbiting Widgets) */
        .float-widget {
            position: absolute;
            z-index: 3;
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1.5px solid rgba(255, 255, 255, 0.45);
            border-radius: 18px;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.35);
            white-space: nowrap;
        }

        /* Widget 1: Đơn hàng mới (Top Left) */
        .widget-order {
            top: 20px;
            left: 10px;
            animation: floatWidget1 7s ease-in-out infinite alternate;
        }

        @keyframes floatWidget1 {
            0% { transform: translateY(0px) rotate(-2deg); }
            100% { transform: translateY(-16px) rotate(2deg); }
        }

        /* Widget 2: Doanh số tăng trưởng (Bottom Right) */
        .widget-revenue {
            bottom: 25px;
            right: 15px;
            animation: floatWidget2 6.5s ease-in-out infinite alternate;
            animation-delay: 0.5s;
        }

        @keyframes floatWidget2 {
            0% { transform: translateY(0px) rotate(2deg); }
            100% { transform: translateY(14px) rotate(-1deg); }
        }

        /* Widget 3: Khách hàng online (Top Right) */
        .widget-users {
            top: 35px;
            right: 0px;
            animation: floatWidget3 8s ease-in-out infinite alternate;
            animation-delay: 1s;
        }

        @keyframes floatWidget3 {
            0% { transform: translateY(0px); }
            100% { transform: translateY(-12px); }
        }

        .widget-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-content: center;
            color: #ffffff;
            font-size: 18px;
        }

        .widget-icon-pink {
            background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%);
            box-shadow: 0 4px 15px rgba(236, 72, 153, 0.5);
        }

        .widget-icon-green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.5);
        }

        .widget-icon-cyan {
            background: linear-gradient(135deg, #06b6d4 0%, #0284c7 100%);
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.5);
        }

        .widget-title {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        .widget-val {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 1080px) {
            .login-container {
                grid-template-columns: 1fr;
                gap: 50px;
                max-width: 580px;
            }

            .showcase-column {
                padding-left: 0;
                text-align: center;
                order: -1;
            }

            .showcase-desc {
                margin: 10px auto 0;
            }

            .dashboard-stage {
                display: none; /* Ẩn mockup phức tạp trên màn hình vừa để tinh gọn */
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 16px 12px;
            }

            .showcase-title {
                font-size: 28px;
            }

            .blob-card {
                padding: 40px 24px;
                border-radius: 36px !important;
                animation: none;
            }

            .blob-glow {
                border-radius: 40px !important;
                animation: none;
            }

            .left-badges-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .badge-card {
                flex-direction: row;
                text-align: left;
                padding: 14px 16px;
            }

            .badge-icon-box {
                width: 48px;
                height: 48px;
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <!-- Ambient floating glowing orbs -->
    <div class="ambient-glow-1" aria-hidden="true"></div>
    <div class="ambient-glow-2" aria-hidden="true"></div>

    <main class="login-container">
        <!-- ================= CỘT TRÁI: FORM ĐĂNG NHẬP + MẤY HÌNH BÊN DƯỚI TO RÕ ================= -->
        <section class="left-column" aria-label="Biểu mẫu đăng nhập quản trị">
            <div class="blob-wrapper">
                <!-- Neon Cyan Glow Shape Behind -->
                <div class="blob-glow" aria-hidden="true"></div>

                <!-- Main Liquid Blob Card -->
                <div class="blob-card">
                    <h1 class="blob-title">Đăng nhập</h1>

                    @if(isset($errors) && $errors->any())
                        <div class="error-badge" role="alert">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.attempt') }}" novalidate>
                        @csrf
                        
                        <!-- Username / Email -->
                        <div class="input-group">
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="pill-input" 
                                placeholder="Email đăng nhập" 
                                value="{{ old('email') }}" 
                                required 
                                autofocus 
                                autocomplete="username"
                            >
                            <span class="input-icon-right" aria-hidden="true">
                                <i class="fa-solid fa-envelope"></i>
                            </span>
                        </div>

                        <!-- Password -->
                        <div class="input-group">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="pill-input" 
                                placeholder="Mật khẩu" 
                                required 
                                autocomplete="current-password"
                            >
                            <button type="button" class="input-icon-right" id="toggle-password" aria-label="Hiện hoặc ẩn mật khẩu">
                                <i class="fa-solid fa-eye" id="toggle-password-icon"></i>
                            </button>
                        </div>

                        <!-- Remember Me Option -->
                        <div class="form-footer-options">
                            <label class="custom-remember">
                                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                                <span>Ghi nhớ đăng nhập</span>
                            </label>
                        </div>

                        <!-- Sign In Pill Button -->
                        <button type="submit" class="pill-submit-btn">
                            <span>Đăng nhập</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>

                        <!-- Sub Links: Forgot Password & Return Home -->
                        <nav class="footer-links" aria-label="Liên kết trợ giúp đăng nhập">
                            <a href="{{ route('client.auth.forgot-password') }}" class="footer-link">
                                <i class="fa-solid fa-key"></i>
                                <span>Quên mật khẩu?</span>
                            </a>
                            <a href="{{ route('client.home.index') }}" class="footer-link">
                                <i class="fa-solid fa-arrow-left"></i>
                                <span>Về trang chủ</span>
                            </a>
                        </nav>
                    </form>
                </div>
            </div>

            <!-- MẤY HÌNH BÊN DƯỚI TO RA: 3 THẺ HUY HIỆU 3D GLASS SANG TRỌNG -->
            <div class="left-badges-grid" aria-label="Cam kết bảo mật và hiệu năng hệ thống">
                <!-- Badge 1: Framework Laravel -->
                <div class="badge-card">
                    <div class="badge-icon-box icon-box-laravel">
                        <i class="fa-brands fa-laravel"></i>
                    </div>
                    <div class="badge-card-title">Laravel 12</div>
                    <div class="badge-card-desc">Framework lõi hiện đại</div>
                </div>

                <!-- Badge 2: Bảo mật SSL -->
                <div class="badge-card">
                    <div class="badge-icon-box icon-box-security">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="badge-card-title">SSL 256-Bit</div>
                    <div class="badge-card-desc">Bảo mật đa tầng</div>
                </div>

                <!-- Badge 3: Hiệu năng cao -->
                <div class="badge-card">
                    <div class="badge-icon-box icon-box-speed">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div class="badge-card-title">Tốc Độ Cao</div>
                    <div class="badge-card-desc">Tối ưu phản hồi &lt;0.1s</div>
                </div>
            </div>
        </section>

        <!-- ================= CỘT PHẢI: HOẠT HỌA 3D DASHBOARD Ý NGHĨA & ĐẸP MẮT ================= -->
        <aside class="showcase-column" aria-label="Giới thiệu hệ thống quản trị NobiFashion">
            <header class="showcase-header">
                <span class="showcase-tag">
                    <i class="fa-solid fa-sparkles"></i>
                    NobiFashion Ecosystem
                </span>
                <h2 class="showcase-title">
                    Hệ Thống Quản Trị
                    <span class="showcase-title-highlight">Thời Trang Toàn Diện</span>
                </h2>
                <p class="showcase-desc">
                    Giám sát doanh thu trực tiếp, quản lý sản phẩm, đơn hàng và kho vận với hiệu năng tối ưu và trải nghiệm mượt mà.
                </p>
            </header>

            <!-- Sân khấu 3D Dashboard Mockup Hoạt Họa -->
            <div class="dashboard-stage" aria-hidden="true">
                <!-- Vòng hào quang quỹ đạo xoay -->
                <div class="orbit-ring"></div>

                <!-- Thẻ trung tâm (Live Analytics Board) -->
                <div class="main-dashboard-card">
                    <div class="card-top-bar">
                        <div class="card-brand">
                            <div class="brand-logo-icon">
                                <i class="fa-solid fa-shirt"></i>
                            </div>
                            <span class="brand-text">Nobi Fashion Live</span>
                        </div>
                        <div class="live-status-pill">
                            <span class="live-dot"></span>
                            <span>Trực tiếp</span>
                        </div>
                    </div>

                    <!-- Mini Live Chart Columns -->
                    <div class="chart-visual-box">
                        <div class="chart-stats-row">
                            <div>
                                <div class="stat-label">Doanh thu hôm nay</div>
                                <div class="stat-value">28.450.000 đ</div>
                            </div>
                            <span class="stat-growth">
                                <i class="fa-solid fa-arrow-trend-up"></i> +28.5%
                            </span>
                        </div>

                        <!-- Cột biểu đồ nhịp sóng sóng động -->
                        <div class="animated-bars">
                            <div class="chart-bar"></div>
                            <div class="chart-bar"></div>
                            <div class="chart-bar"></div>
                            <div class="chart-bar"></div>
                            <div class="chart-bar"></div>
                            <div class="chart-bar"></div>
                            <div class="chart-bar"></div>
                        </div>
                    </div>

                    <!-- Chỉ số phụ -->
                    <div class="card-metrics-row">
                        <div class="metric-item">
                            <div class="metric-title">Tỷ lệ hoàn tất</div>
                            <div class="metric-num">98.6%</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-title">Tốc độ xử lý</div>
                            <div class="metric-num">0.05 giây</div>
                        </div>
                    </div>
                </div>

                <!-- Vệ tinh bay 1: Đơn hàng mới (Top Left) -->
                <div class="float-widget widget-order">
                    <div class="widget-icon widget-icon-pink">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>
                    <div>
                        <div class="widget-title">Đơn hàng mới</div>
                        <div class="widget-val">+186 Đơn</div>
                    </div>
                </div>

                <!-- Vệ tinh bay 2: Doanh số bán lẻ (Bottom Right) -->
                <div class="float-widget widget-revenue">
                    <div class="widget-icon widget-icon-green">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div>
                        <div class="widget-title">Tăng trưởng tháng</div>
                        <div class="widget-val">+42.8%</div>
                    </div>
                </div>

                <!-- Vệ tinh bay 3: Khách hàng online (Top Right) -->
                <div class="float-widget widget-users">
                    <div class="widget-icon widget-icon-cyan">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <div class="widget-title">Khách đang online</div>
                        <div class="widget-val">1.240 người</div>
                    </div>
                </div>
            </div>
        </aside>
    </main>

    <script>
        // Toggle view password
        const toggleBtn = document.getElementById('toggle-password');
        const passInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggle-password-icon');

        if (toggleBtn && passInput && toggleIcon) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const isPassword = passInput.type === 'password';
                passInput.type = isPassword ? 'text' : 'password';
                toggleIcon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
            });
        }
    </script>
</body>
</html>
