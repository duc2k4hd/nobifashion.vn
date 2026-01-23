<x-mail::message>
    # Thông báo từ {{ config('app.name') }}

    Xin chào {{ $subscription->email }},

    @if (!empty($content))
        {!! $content !!}
    @else
        Chúng tôi có những thông tin và ưu đãi đặc biệt dành cho bạn!
    @endif

    @if (!empty($cta_url) && !empty($cta_text))
        <x-mail::button :url="$cta_url">
            {{ $cta_text }}
        </x-mail::button>
    @endif

    @if (!empty($footer))
        <x-mail::panel>
            {{ $footer }}
        </x-mail::panel>
    @endif

    Trân trọng,<br>
    {{ config('app.name') }}

    ---

    <small style="color: #999;">
        Bạn nhận được email này vì đã đăng ký nhận thông báo từ {{ config('app.name') }}.
        <br>
        @if (!empty($subscription->verify_token))
            <a href="{{ route('newsletter.unsubscribe', ['token' => $subscription->verify_token]) }}"
                style="color: #999;">Hủy đăng ký</a>
        @endif
    </small>
</x-mail::message>
