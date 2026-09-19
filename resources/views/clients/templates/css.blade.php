<!-- ✅ Preload CSS quan trọng -->
<link rel="preload" href="{{ asset('clients/assets/css/main.css') }}?v={{ env('APP_VERSION') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="{{ asset('clients/assets/css/header.css') }}?v={{ env('APP_VERSION') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">

<!-- ⚠️ Preload responsive.css chỉ khi truy cập chủ yếu từ mobile -->
<link rel="preload" href="{{ asset('clients/assets/css/responsive.css') }}?v={{ env('APP_VERSION') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">

<!-- ✅ Fallback nếu người dùng tắt JavaScript -->
<noscript>
  <link rel="stylesheet" href="{{ asset('clients/assets/css/main.css') }}?v={{ env('APP_VERSION') }}">
  <link rel="stylesheet" href="{{ asset('clients/assets/css/header.css') }}?v={{ env('APP_VERSION') }}">
  <link rel="stylesheet" href="{{ asset('clients/assets/css/responsive.css') }}?v={{ env('APP_VERSION') }}">
</noscript>

<!-- ✅ Load các CSS phụ không cần preload -->
<link rel="stylesheet" href="{{ asset('clients/assets/css/footer.css') }}?v={{ env('APP_VERSION') }}">
<link rel="stylesheet" href="{{ asset('clients/assets/css/call_to_action.css') }}?v={{ env('APP_VERSION') }}">