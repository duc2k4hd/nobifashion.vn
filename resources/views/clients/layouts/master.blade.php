<!DOCTYPE html>
<html lang="{{ $settings->site_language ?? 'vi' }}">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <script>
    const sessionToken = {!! json_encode(session('session_token')) !!};
    </script>
    @yield('head')
    @include('clients.templates.css')
    @yield('schema')
    @include('clients.templates.head')
    <title>@yield('title')</title>
</head>

<body class="@yield('body_class')">
    {!! $settings->google_tag_body !!}
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
    @include('clients.templates.chat')
    @include('clients.templates.js')
    @yield('foot')
    {!! $settings->google_tag_header ?? $settings->google_analytics ?? '' !!}
</body>

</html>
