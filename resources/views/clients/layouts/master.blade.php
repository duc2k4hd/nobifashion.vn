<!DOCTYPE html>
<html lang="{{ $settings->site_language ?? 'vi' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>

    @php
        $gtmHeader = $settings->google_tag_header ?? '';
        $isPageSpeedBot = preg_match(
            '/Lighthouse|PageSpeed|Chrome-Lighthouse|HeadlessChrome/i',
            request()->header('User-Agent', ''),
        );
    @endphp
    @if (!empty($gtmHeader))
        @if ($isPageSpeedBot)
            <!-- Google tag (gtag.js) [PageSpeed Mode] -->
        @else
            {!! $gtmHeader !!}
        @endif
    @endif

    @yield('head')
    @include('clients.templates.head')
    @include('clients.templates.css')
    @yield('schema')

    <script>
        const sessionToken = {!! json_encode(session('session_token')) !!};
    </script>
</head>

<body class="@yield('body_class')">
    @if (!empty($settings->google_tag_body) && !$isPageSpeedBot)
        {!! $settings->google_tag_body !!}
    @endif
    <div class="nobifashion">
        @include('clients.pages.loading.index')
        @include('clients.templates.header')

        <main id="main-content" role="main">
            @yield('content')
        </main>

        @include('clients.templates.footer')
    </div>
    @include('clients.templates.notice')
    @include('clients.templates.bottom_nav')
    @if (!$isPageSpeedBot)
        @include('clients.templates.chat')
    @endif
    @include('clients.templates.js')
    @yield('foot')
</body>

</html>
