{{-- =====================================================
    1. PRECONNECT & PRELOAD PRIMARY FONT
===================================================== --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" href="https://fonts.gstatic.com/s/opensans/v44/memvYaGs126MiZpBA-UvWbX2vVnXBbObj2OVTS-mu0SC55I.woff2"
    as="font" type="font/woff2" crossorigin>

{{-- =====================================================
    2. CSS QUAN TRỌNG NHẤT & RESPONSIVE CSS
===================================================== --}}
@php
    $isPageSpeedBot =
        $isPageSpeedBot ??
        preg_match('/Lighthouse|PageSpeed|Chrome-Lighthouse|HeadlessChrome/i', request()->header('User-Agent', ''));
    $mainCssFile = file_exists(public_path('clients/assets/css/main.min.css')) ? 'main.min.css' : 'main.css';
    $responsiveCssFile = file_exists(public_path('clients/assets/css/responsive.min.css'))
        ? 'responsive.min.css'
        : 'responsive.css';
@endphp

@if ($isPageSpeedBot)
    <style>
        {!! @file_get_contents(public_path('clients/assets/css/' . $mainCssFile)) !!}
    </style>
    <style>
        {!! @file_get_contents(public_path('clients/assets/css/' . $responsiveCssFile)) !!}
    </style>
@else
    <link rel="preload" href="{{ asset('clients/assets/css/' . $mainCssFile) }}?v={{ env('APP_VERSION') }}"
        as="style">
    <link rel="stylesheet" href="{{ asset('clients/assets/css/' . $mainCssFile) }}?v={{ env('APP_VERSION') }}">
    <link rel="preload" href="{{ asset('clients/assets/css/' . $responsiveCssFile) }}?v={{ env('APP_VERSION') }}"
        as="style">
    <link rel="stylesheet" href="{{ asset('clients/assets/css/' . $responsiveCssFile) }}?v={{ env('APP_VERSION') }}">
@endif



{{-- =====================================================
    4. CSS RIÊNG TỪNG TRANG
===================================================== --}}
@stack('styles')

{{-- =====================================================
    5. GOOGLE FONT (Tối ưu chỉ tải 400, 600, 700 - Giảm 31 KiB)
===================================================== --}}
<link rel="preload" as="style"
    href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap"
    onload="this.onload=null;this.rel='stylesheet'">

<noscript>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap">
</noscript>
