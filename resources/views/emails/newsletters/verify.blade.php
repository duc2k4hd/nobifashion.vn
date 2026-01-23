<x-mail::message>
    # Xác nhận đăng ký nhận thông báo

    Xin chào,

    Cảm ơn bạn đã đăng ký nhận thông báo từ **{{ config('app.name') }}**!

    Để hoàn tất đăng ký, vui lòng nhấn nút bên dưới để xác nhận địa chỉ email của bạn.

    <x-mail::button :url="$verifyUrl">
        Xác nhận đăng ký
    </x-mail::button>

    Liên kết này sẽ hết hạn sau 7 ngày.

    <x-mail::panel>
        Nếu bạn không đăng ký nhận thông báo này, vui lòng bỏ qua email này.
    </x-mail::panel>

    Trân trọng,<br>
    {{ config('app.name') }}
</x-mail::message>
