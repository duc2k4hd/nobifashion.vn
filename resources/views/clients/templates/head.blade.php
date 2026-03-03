<meta name="author" content="{{ $settings->seo_author ?? 'NOBI FASHION' }}">

<link rel="apple-touch-icon" sizes="180x180"
    href="{{ asset('/clients/assets/img/business/' . ($settings->site_favicon ?? 'favicon.png')) }}?v={{ time() }}">
<link rel="icon" type="image/png" sizes="32x32"
    href="{{ asset('/clients/assets/img/business/' . ($settings->site_favicon ?? 'favicon.png')) }}?v={{ time() }}">
<link rel="icon" type="image/png" sizes="16x16"
    href="{{ asset('/clients/assets/img/business/' . ($settings->site_favicon ?? 'favicon.png')) }}?v={{ time() }}">
<link rel="mask-icon"
    href="{{ asset('clients/assets/img/business/' . ($settings->site_favicon ?? 'favicon.png')) }}?v={{ time() }}"
    color="#5bbad5">
<link rel="icon"
    href="{{ asset('clients/assets/img/business/' . ($settings->site_favicon ?? 'favicon.png')) }}?v={{ time() }}"
    type="image/x-icon">
<meta name="theme-color" content="#ff3366">

<meta http-equiv="Strict-Transport-Security" content="max-age=31536000; includeSubDomains">
<meta http-equiv="X-Content-Type-Options" content="nosniff">
<meta http-equiv="X-XSS-Protection" content="1; mode=block">
<meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
<meta name='dmca-site-verification' content='MFBmVDJ4N2sybDVocEJZUzZCaTlPQT090' />

{!! $settings->google_tag_header ?? $settings->google_analytics ?? '' !!}