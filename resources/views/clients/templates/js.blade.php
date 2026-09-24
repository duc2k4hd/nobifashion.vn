<script src="{{ asset('clients/assets/js/main.js') }}?v={{ env('APP_VERSION') }}"></script>
<script defer src="{{ asset('clients/assets/js/header.js') }}?v={{ env('APP_VERSION') }}"></script>
<script defer src="{{ asset('clients/assets/js/customer_chat.js') }}?v={{ env('APP_VERSION') }}"></script>

@php
    $alerts = [
        'success' => session('success'),
        'error'   => session('error'),
        'warning' => session('warning'),
        'info'    => session('info'),
    ];
@endphp

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let alerts = [];
        @foreach ($alerts as $type => $message)
            @if ($message)
                alerts.push({type: '{{ $type }}', message: @json($message)});
            @endif
        @endforeach

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                alerts.push({type: 'error', message: @json($error)});
            @endforeach
        @endif

        alerts.forEach(a => showCustomToast(a.message, a.type));
    });
</script>



